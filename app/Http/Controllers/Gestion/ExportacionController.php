<?php

namespace App\Http\Controllers\Gestion;

use App\Actions\Exportaciones\EscritorDeArchivo;
use App\Actions\Exportaciones\ExportacionDemasiadoGrande;
use App\Actions\Exportaciones\Registro;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exportación de cualquier módulo registrado (ver Registro) en CSV, TXT,
 * XLSX o PDF. Exportar exige el permiso de ver del módulo.
 */
class ExportacionController extends Controller
{
    use EsCrudDeGestion;

    public function __invoke(string $modulo, string $formato, EscritorDeArchivo $escritor): Response|RedirectResponse
    {
        $exportador = Registro::exportador($modulo) ?? abort(404);
        in_array($formato, EscritorDeArchivo::FORMATOS, true) || abort(404);

        Gate::authorize("{$exportador->modulo()->value}.ver");

        try {
            return $escritor->descargar($exportador, $formato, $modulo);
        } catch (ExportacionDemasiadoGrande $excepcion) {
            return $this->rechazarEliminacion(
                "El PDF admite hasta {$excepcion->maximo} filas. Exporta a Excel o CSV.",
                'gestion.empresa.edit',
            );
        }
    }
}
