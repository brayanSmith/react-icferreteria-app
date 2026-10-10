<?php

namespace App\Jobs;

use App\Actions\Importaciones\FilaRechazada;
use App\Actions\Importaciones\Importador;
use App\Actions\Importaciones\LectorCsv;
use App\Actions\Importaciones\Registro;
use App\Models\Importacion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Procesa un CSV importado: lo lee fila por fila, toma de cada fila las
 * columnas que el usuario relacionó, valida con las reglas del módulo, guarda
 * (crea o actualiza según el modo) y anota las rechazadas. Corre como el
 * usuario que subió el archivo para que todo quede en su empresa (tenant).
 */
class ProcesarImportacion implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /** Cada cuántas filas se actualiza el avance que ve el usuario. */
    private const LOTE_DE_AVANCE = 100;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public Importacion $importacion) {}

    public function handle(): void
    {
        $importacion = $this->importacion;
        $importador = Registro::importador($importacion->modulo);

        if ($importador === null || $importacion->ruta === null || $importacion->mapeo === null) {
            $this->fallar('El módulo o el archivo de la importación ya no existe.');

            return;
        }

        // Se restaura el usuario anterior: con la cola síncrona el trabajo corre dentro de la petición.
        $usuarioAnterior = Auth::user();
        $permisos = app(PermissionRegistrar::class);
        $equipoAnterior = $permisos->getPermissionsTeamId();

        Auth::setUser($importacion->user);
        // Los roles de Spatie se asignan por empresa: el trabajo debe saber en cuál corre.
        $permisos->setPermissionsTeamId($importacion->user->tenant_id);

        try {
            $this->procesar($importacion, $importador);
        } finally {
            $permisos->setPermissionsTeamId($equipoAnterior);
            $usuarioAnterior !== null ? Auth::setUser($usuarioAnterior) : Auth::forgetUser();
        }
    }

    public function failed(Throwable $excepcion): void
    {
        $this->fallar('No se pudo procesar el archivo: '.$excepcion->getMessage());
    }

    private function procesar(Importacion $importacion, Importador $importador): void
    {
        $ruta = $importacion->ruta;
        $lector = new LectorCsv(Storage::disk('local')->path($ruta));
        $modo = $importacion->modo;
        $mapeo = $importacion->mapeo;

        $importacion->update([
            'estado' => Importacion::PROCESANDO,
            'delimitador' => $lector->delimitador(),
            'total_filas' => $lector->totalFilas(),
        ]);

        $contadores = ['procesadas' => 0, 'creadas' => 0, 'actualizadas' => 0, 'omitidas' => 0, 'errores' => 0];
        $errores = [];

        foreach ($lector->filas() as $registro => $celdas) {
            $datos = [];
            foreach (array_keys($importador->columnas()) as $clave) {
                $posicion = $mapeo[$clave] ?? null;
                $datos[$clave] = $posicion === null ? null : ($celdas[$posicion] ?? null);
            }

            $datos = $importador->normalizar($datos);

            // En modo "ambos" cada fila decide: con ID actualiza, sin ID crea.
            $modoDeFila = $importador->modoDeFila($datos, $modo);
            $validador = Validator::make($datos, $importador->reglas($modoDeFila), $importador->mensajes(), $importador->columnas());

            if ($validador->fails()) {
                $contadores['errores']++;
                $this->anotarError($errores, $registro, $validador->errors()->first());
            } else {
                try {
                    if ($importador->guardar($datos, $modoDeFila)) {
                        $contadores[$modoDeFila === Importacion::MODO_ACTUALIZAR ? 'actualizadas' : 'creadas']++;
                    } else {
                        $contadores['omitidas']++;
                    }
                } catch (FilaRechazada $rechazo) {
                    $contadores['errores']++;
                    $this->anotarError($errores, $registro, $rechazo->getMessage());
                } catch (Throwable $excepcion) {
                    report($excepcion);
                    $contadores['errores']++;
                    $this->anotarError($errores, $registro, 'No se pudo guardar esta fila.');
                }
            }

            $contadores['procesadas']++;

            if ($contadores['procesadas'] % self::LOTE_DE_AVANCE === 0) {
                $this->guardarAvance($importacion, $contadores);
            }
        }

        $this->guardarAvance($importacion, $contadores, [
            'estado' => Importacion::COMPLETADA,
            'errores' => $errores,
            'ruta' => null,
        ]);

        Storage::disk('local')->delete($ruta);
    }

    /**
     * @param  list<array{fila: int, mensaje: string}>  $errores
     */
    private function anotarError(array &$errores, int $registro, string $mensaje): void
    {
        if (count($errores) < Importacion::MAX_ERRORES_GUARDADOS) {
            $errores[] = ['fila' => $registro, 'mensaje' => $mensaje];
        }
    }

    /**
     * @param  array{procesadas: int, creadas: int, actualizadas: int, omitidas: int, errores: int}  $contadores
     * @param  array<string, mixed>  $extra
     */
    private function guardarAvance(Importacion $importacion, array $contadores, array $extra = []): void
    {
        $importacion->update([
            'filas_procesadas' => $contadores['procesadas'],
            'filas_creadas' => $contadores['creadas'],
            'filas_actualizadas' => $contadores['actualizadas'],
            'filas_omitidas' => $contadores['omitidas'],
            'filas_con_error' => $contadores['errores'],
            ...$extra,
        ]);
    }

    private function fallar(string $mensaje): void
    {
        if ($this->importacion->ruta !== null) {
            Storage::disk('local')->delete($this->importacion->ruta);
        }

        $this->importacion->update([
            'estado' => Importacion::FALLIDA,
            'mensaje' => mb_substr($mensaje, 0, 250),
            'ruta' => null,
        ]);
    }
}
