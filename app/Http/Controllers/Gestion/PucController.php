<?php

namespace App\Http\Controllers\Gestion;

use App\Actions\Importaciones\PanelDeImportacion;
use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarPucRequest;
use App\Http\Resources\PucResource;
use App\Models\Puc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Plan único de cuentas (PUC) del tenant.
 *
 * Es un listado de cuentas contables con búsqueda y paginación: el PUC suele
 * tener cientos de filas. Todas las consultas quedan limitadas al tenant del
 * usuario por el scope global del modelo.
 */
class PucController extends Controller implements HasMiddleware
{
    use EsCrudDeGestion;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return self::permisosCrud(Modulo::Pucs);
    }

    /**
     * Lista todas las cuentas del plan: la búsqueda, el orden y la paginación
     * las hace el navegador.
     */
    public function index(): Response
    {
        $cuentas = Puc::query()->orderBy('subcuenta')->get();

        return Inertia::render('gestion/pucs/index', [
            'cuentas' => PucResource::collection($cuentas),
            ...PanelDeImportacion::props(['pucs']),
        ]);
    }

    /**
     * Crea una cuenta. El campo "concatenar_subcuenta_concepto" se arma solo.
     */
    public function store(GuardarPucRequest $request): RedirectResponse
    {
        Puc::create($this->conConcatenado($request->validated()));

        $this->avisarExito('Cuenta creada.');

        return to_route('gestion.pucs.index');
    }

    /**
     * Actualiza una cuenta y recalcula su texto concatenado.
     */
    public function update(GuardarPucRequest $request, Puc $puc): RedirectResponse
    {
        $puc->update($this->conConcatenado($request->validated()));

        $this->avisarExito('Cuenta actualizada.');

        return to_route('gestion.pucs.index');
    }

    /**
     * Elimina una cuenta.
     */
    public function destroy(Puc $puc): RedirectResponse
    {
        $puc->delete();

        $this->avisarExito('Cuenta eliminada.');

        return to_route('gestion.pucs.index');
    }

    /**
     * Agrega el texto "subcuenta - concepto" que usan los selectores de cuentas.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function conConcatenado(array $datos): array
    {
        $datos['concatenar_subcuenta_concepto'] = Puc::textoConcatenado($datos['subcuenta'], $datos['concepto']);

        return $datos;
    }
}
