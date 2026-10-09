<?php

namespace App\Http\Controllers\Gestion;

use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarRutaRequest;
use App\Http\Resources\RutaResource;
use App\Models\Ruta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rutas de reparto a las que se asignan los clientes.
 *
 * Catálogo corto de cada tenant, listado completo y sin paginar.
 */
class RutaController extends Controller implements HasMiddleware
{
    use EsCrudDeGestion;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return self::permisosCrud(Modulo::Rutas);
    }

    /**
     * Lista las rutas con la cantidad de clientes de cada una.
     */
    public function index(): Response
    {
        $rutas = Ruta::query()
            ->withCount('clientes')
            ->orderBy('ruta')
            ->get();

        return Inertia::render('gestion/rutas/index', [
            'rutas' => RutaResource::collection($rutas),
        ]);
    }

    /**
     * Crea una ruta.
     */
    public function store(GuardarRutaRequest $request): RedirectResponse
    {
        Ruta::create($request->validated());

        $this->avisarExito('Ruta creada.');

        return to_route('gestion.rutas.index');
    }

    /**
     * Actualiza una ruta.
     */
    public function update(GuardarRutaRequest $request, Ruta $ruta): RedirectResponse
    {
        $ruta->update($request->validated());

        $this->avisarExito('Ruta actualizada.');

        return to_route('gestion.rutas.index');
    }

    /**
     * Elimina una ruta, salvo que tenga clientes: la base los borraría en
     * cascada, incluidos los que están eliminados lógicamente.
     */
    public function destroy(Ruta $ruta): RedirectResponse
    {
        if ($ruta->clientes()->withTrashed()->exists()) {
            return $this->rechazarEliminacion(
                'No puedes eliminar esta ruta porque tiene clientes asignados.',
                'gestion.rutas.index',
            );
        }

        $ruta->delete();

        $this->avisarExito('Ruta eliminada.');

        return to_route('gestion.rutas.index');
    }
}
