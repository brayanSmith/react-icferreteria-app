<?php

namespace App\Actions\Importaciones;

use App\Http\Resources\ImportacionResource;
use App\Models\Importacion;
use Illuminate\Support\Facades\Auth;

/**
 * Datos que necesita la pantalla de un módulo para importar: las
 * importaciones recientes y, si el usuario subió un archivo y todavía no
 * relacionó sus columnas, la configuración pendiente.
 */
class PanelDeImportacion
{
    /**
     * @param  list<string>  $importables  Claves de los importadores que usa la pantalla.
     * @return array<string, mixed>
     */
    public static function props(array $importables): array
    {
        return [
            'importaciones' => ImportacionResource::collection(
                Importacion::recientesDe($importables)->where('estado', '!=', Importacion::CONFIGURANDO)->get(),
            ),
            'importacionPendiente' => self::pendiente($importables),
        ];
    }

    /**
     * @param  list<string>  $importables
     * @return array<string, mixed>|null
     */
    private static function pendiente(array $importables): ?array
    {
        $importacion = Importacion::query()
            ->whereIn('modulo', $importables)
            ->where('user_id', Auth::id())
            ->where('estado', Importacion::CONFIGURANDO)
            ->latest('id')
            ->first();

        $importador = $importacion === null ? null : Registro::importador($importacion->modulo);

        if ($importacion === null || $importador === null) {
            return null;
        }

        return [
            'id' => $importacion->id,
            'etiqueta' => $importador->etiqueta(),
            'nombre_original' => $importacion->nombre_original,
            'delimitador' => $importacion->delimitador,
            'encabezados' => $importacion->encabezados ?? [],
            'muestra' => $importacion->muestra ?? [],
            'sugerencia' => $importador->sugerirMapeo($importacion->encabezados ?? []),
            'modos' => $importador->modosPermitidos(),
            'identificador' => $importador->identificador(),
            'aviso' => $importador->aviso(),
            'columnas' => collect($importador->columnas())
                ->map(fn (string $etiqueta, string $clave): array => [
                    'clave' => $clave,
                    'etiqueta' => $etiqueta,
                    'requerida_crear' => in_array($clave, $importador->columnasRequeridas(Importacion::MODO_CREAR), true),
                    'requerida_actualizar' => in_array($clave, $importador->columnasRequeridas(Importacion::MODO_ACTUALIZAR), true),
                ])
                ->values()
                ->all(),
        ];
    }
}
