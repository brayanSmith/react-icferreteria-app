<?php

namespace App\Http\Controllers\Gestion;

use App\Actions\Importaciones\Importador;
use App\Actions\Importaciones\LectorCsv;
use App\Actions\Importaciones\Registro;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\ConfirmarImportacionRequest;
use App\Http\Requests\Gestion\SubirImportacionRequest;
use App\Jobs\ProcesarImportacion;
use App\Models\Importacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Csv\Bom;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Importación de archivos CSV de cualquier módulo registrado (ver Registro).
 *
 * Va en dos pasos: primero se sube el archivo (el sistema detecta sus
 * columnas) y luego el usuario las relaciona con las del sistema, elige si
 * crea o actualiza y confirma. Entonces se procesa en segundo plano y la
 * pantalla del módulo muestra el avance. Importar exige el permiso de crear
 * del módulo.
 */
class ImportacionController extends Controller
{
    use EsCrudDeGestion;

    /**
     * Guarda el archivo subido y detecta sus columnas; la pantalla del módulo
     * abre entonces el paso de relacionarlas.
     */
    public function store(SubirImportacionRequest $request, string $modulo): RedirectResponse
    {
        $importador = $this->importadorAutorizado($modulo);
        $archivo = $request->file('archivo');

        $ruta = $archivo->store("importaciones/{$request->user()->tenant_id}", 'local');
        $lector = new LectorCsv(Storage::disk('local')->path($ruta));
        $encabezados = $lector->encabezados();

        if ($encabezados === []) {
            Storage::disk('local')->delete($ruta);

            throw ValidationException::withMessages(['archivo' => 'El archivo está vacío.']);
        }

        // Una sola configuración pendiente por usuario: la anterior se descarta.
        Importacion::query()
            ->where('user_id', $request->user()->id)
            ->where('estado', Importacion::CONFIGURANDO)
            ->get()
            ->each(fn (Importacion $anterior) => $this->descartar($anterior));

        Importacion::create([
            'user_id' => $request->user()->id,
            'modulo' => $modulo,
            'nombre_original' => $archivo->getClientOriginalName(),
            'ruta' => $ruta,
            'estado' => Importacion::CONFIGURANDO,
            'delimitador' => $lector->delimitador(),
            'encabezados' => $encabezados,
            'muestra' => $lector->muestra(),
        ]);

        return redirect()->back();
    }

    /**
     * Recibe la relación de columnas y el modo (crear o actualizar) y encola
     * el procesamiento.
     */
    public function confirmar(ConfirmarImportacionRequest $request, Importacion $importacion): RedirectResponse
    {
        $this->importadorAutorizado($importacion->modulo);

        if ($importacion->estado !== Importacion::CONFIGURANDO) {
            abort(404);
        }

        $importacion->update([
            'modo' => $request->modo(),
            'mapeo' => $request->mapeo(),
            'estado' => Importacion::PENDIENTE,
        ]);

        ProcesarImportacion::dispatch($importacion);

        $this->avisarExito('Importación iniciada. Se está procesando en segundo plano.');

        return redirect()->back();
    }

    /**
     * Cancela una importación que aún no se confirmó y borra su archivo.
     */
    public function destroy(Importacion $importacion): RedirectResponse
    {
        $this->importadorAutorizado($importacion->modulo);

        if ($importacion->estado === Importacion::CONFIGURANDO) {
            $this->descartar($importacion);
        }

        return redirect()->back();
    }

    /**
     * Descarga la plantilla del módulo: los encabezados y unas filas de ejemplo.
     */
    public function plantilla(string $modulo): StreamedResponse
    {
        $importador = $this->importadorAutorizado($modulo);

        return response()->streamDownload(function () use ($importador): void {
            $escritor = Writer::createFromStream(fopen('php://output', 'w'));
            $escritor->setDelimiter(';');
            $escritor->setOutputBOM(Bom::Utf8);

            $escritor->insertOne(array_values($importador->columnas()));
            $escritor->insertAll($importador->ejemplos());
        }, "plantilla-{$modulo}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * El importador del módulo, si existe y el usuario puede crear en él.
     */
    private function importadorAutorizado(string $modulo): Importador
    {
        $importador = Registro::importador($modulo) ?? abort(404);
        Gate::authorize("{$importador->modulo()->value}.crear");

        return $importador;
    }

    private function descartar(Importacion $importacion): void
    {
        if ($importacion->ruta !== null) {
            Storage::disk('local')->delete($importacion->ruta);
        }

        $importacion->delete();
    }
}
