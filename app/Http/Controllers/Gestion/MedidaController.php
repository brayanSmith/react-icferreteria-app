<?php

namespace App\Http\Controllers\Gestion;

use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarMedidaRequest;
use App\Http\Resources\MedidaResource;
use App\Models\Medida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Unidades de medida (kilo, metro, unidad...) con las que se miden los productos.
 *
 * Es un catálogo corto, por eso se lista completo y sin paginar. Cada tenant
 * tiene las suyas.
 */
class MedidaController extends Controller implements HasMiddleware
{
    use EsCrudDeGestion;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return self::permisosCrud(Modulo::Medidas);
    }

    /**
     * Lista las medidas con la cantidad de productos que usa cada una.
     */
    public function index(Request $request): Response
    {
        $medidas = Medida::query()
            ->withCount('productos')
            ->orderBy('nombre_medida')
            ->get();

        return Inertia::render('gestion/medidas/index', [
            'medidas' => MedidaResource::collection($medidas),
        ]);
    }

    /**
     * Crea una medida.
     */
    public function store(GuardarMedidaRequest $request): RedirectResponse
    {
        Medida::create($request->validated());

        $this->avisarExito('Medida creada.');

        return to_route('gestion.medidas.index');
    }

    /**
     * Actualiza una medida.
     */
    public function update(GuardarMedidaRequest $request, Medida $medida): RedirectResponse
    {
        $medida->update($request->validated());

        $this->avisarExito('Medida actualizada.');

        return to_route('gestion.medidas.index');
    }

    /**
     * Elimina una medida, salvo que algún producto o guía de pedido la use:
     * la base borraría esos productos en cascada.
     */
    public function destroy(Medida $medida): RedirectResponse
    {
        if ($medida->productos()->withTrashed()->exists() || $medida->detallePedidoGuias()->withTrashed()->exists()) {
            return $this->rechazarEliminacion(
                'No puedes eliminar esta medida porque hay productos o guías que la usan.',
                'gestion.medidas.index',
            );
        }

        $medida->delete();

        $this->avisarExito('Medida eliminada.');

        return to_route('gestion.medidas.index');
    }
}
