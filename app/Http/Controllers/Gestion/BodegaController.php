<?php

namespace App\Http\Controllers\Gestion;

use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarBodegaRequest;
use App\Http\Resources\BodegaResource;
use App\Models\Bodega;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Lista las bodegas activas o, con `eliminadas=1`, las eliminadas.
     */
    public function index(Request $request): Response
    {
        $eliminadas = $request->boolean('eliminadas');

        $bodegas = Bodega::query()
            ->withCount('productos')
            ->when($eliminadas, fn ($query) => $query->onlyTrashed())
            ->orderBy('nombre_bodega')
            ->get();

        return Inertia::render('gestion/bodegas/index', [
            'bodegas' => BodegaResource::collection($bodegas),
            'eliminadas' => $eliminadas,
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
     * productos, movimientos o inventario asociados.
     */
    public function destroy(Bodega $bodega): RedirectResponse
    {
        $enUso = $bodega->productos()->withTrashed()->exists()
            || $bodega->pedidos()->withTrashed()->exists()
            || $bodega->compras()->withTrashed()->exists()
            || $bodega->trasladosComoDonante()->withTrashed()->exists()
            || $bodega->trasladosComoDestino()->withTrashed()->exists()
            || $bodega->stockIniciales()->withTrashed()->exists()
            || $bodega->stockBodegas()->exists();

        if ($enUso) {
            return $this->rechazarEliminacion(
                'No puedes eliminar esta bodega porque tiene productos o movimientos asociados.',
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
