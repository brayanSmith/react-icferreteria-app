<?php

namespace App\Http\Controllers\Gestion;

use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarBodegaRequest;
use App\Http\Resources\BodegaResource;
use App\Models\Bodega;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bodegas donde el tenant guarda su inventario.
 *
 * Se eliminan de forma lógica y se pueden restaurar desde "Ver eliminadas".
 */
class BodegaController extends Controller implements HasMiddleware
{
    use EsCrudDeGestion;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return self::permisosCrud(Modulo::Bodegas);
    }

    /**
     * Lista todas las bodegas, eliminadas incluidas: el filtro de eliminadas lo
     * hace el navegador.
     */
    public function index(): Response
    {
        $bodegas = Bodega::query()
            ->withCount(['stockIniciales as productos_count'])
            ->withTrashed()
            ->orderBy('nombre_bodega')
            ->get();

        return Inertia::render('gestion/bodegas/index', [
            'bodegas' => BodegaResource::collection($bodegas),
        ]);
    }

    /**
     * Crea una bodega.
     */
    public function store(GuardarBodegaRequest $request): RedirectResponse
    {
        Bodega::create($request->validated());

        $this->avisarExito('Bodega creada.');

        return to_route('gestion.bodegas.index');
    }

    /**
     * Actualiza una bodega.
     */
    public function update(GuardarBodegaRequest $request, Bodega $bodega): RedirectResponse
    {
        $bodega->update($request->validated());

        $this->avisarExito('Bodega actualizada.');

        return to_route('gestion.bodegas.index');
    }

    /**
     * Elimina (de forma lógica) una bodega, salvo que todavía tenga
     * pedidos, compras, traslados o inventario (stock inicial) asociados.
     */
    public function destroy(Bodega $bodega): RedirectResponse
    {
        $enUso = $bodega->pedidos()->withTrashed()->exists()
            || $bodega->compras()->withTrashed()->exists()
            || $bodega->trasladosComoDonante()->withTrashed()->exists()
            || $bodega->trasladosComoDestino()->withTrashed()->exists()
            || $bodega->stockIniciales()->withTrashed()->exists()
            || $bodega->stockBodegas()->exists();

        if ($enUso) {
            return $this->rechazarEliminacion(
                'No puedes eliminar esta bodega porque tiene inventario o movimientos asociados.',
                'gestion.bodegas.index',
            );
        }

        $bodega->delete();

        $this->avisarExito('Bodega eliminada.');

        return to_route('gestion.bodegas.index');
    }

    /**
     * Restaura una bodega eliminada.
     */
    public function restore(Bodega $bodega): RedirectResponse
    {
        $bodega->restore();

        $this->avisarExito('Bodega restaurada.');

        return to_route('gestion.bodegas.index');
    }
}
