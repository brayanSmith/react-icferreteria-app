<?php

namespace App\Http\Controllers\Gestion;

use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarStockInicialRequest;
use App\Http\Resources\StockInicialResource;
use App\Models\Bodega;
use App\Models\Producto;
use App\Models\StockInicial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Stock inicial: la cantidad con la que arranca cada producto en cada bodega.
 *
 * Se lista con búsqueda por producto y paginación, se elimina de forma lógica y
 * se puede restaurar. Al crear se elige producto y bodega; al editar solo se
 * cambia la cantidad.
 */
class StockInicialController extends Controller implements HasMiddleware
{
    use EsCrudDeGestion;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return self::permisosCrud(Modulo::StockInicial);
    }

    /**
     * Lista los stocks iniciales, filtrando por código o nombre del producto, y
     * opcionalmente solo los eliminados. Incluye los productos y bodegas
     * disponibles para el formulario de alta.
     */
    public function index(Request $request): Response
    {
        $filtros = [
            'buscar' => (string) $request->query('buscar', ''),
            'eliminados' => $request->boolean('eliminados'),
        ];

        $stocks = StockInicial::query()
            // Un producto eliminado sigue mostrándose en el historial.
            ->with([
                'producto' => fn ($query) => $query->withTrashed()->select(['id', 'codigo_producto', 'nombre_producto']),
                'bodega' => fn ($query) => $query->withTrashed()->select(['id', 'nombre_bodega']),
            ])
            ->when($filtros['eliminados'], fn ($query) => $query->onlyTrashed())
            ->when($filtros['buscar'] !== '', fn ($query) => $query->whereHas(
                'producto',
                fn ($producto) => $producto->withTrashed()->where(function ($producto) use ($filtros): void {
                    $producto->where('codigo_producto', 'like', "%{$filtros['buscar']}%")
                        ->orWhere('nombre_producto', 'like', "%{$filtros['buscar']}%");
                }),
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('gestion/stock-inicial/index', [
            'stocks' => StockInicialResource::collection($stocks),
            'filtros' => $filtros,
            'productos' => Producto::query()
                ->orderBy('nombre_producto')
                ->get(['id', 'codigo_producto', 'nombre_producto'])
                ->map(fn (Producto $producto): array => [
                    'id' => $producto->id,
                    'nombre' => "{$producto->codigo_producto} - {$producto->nombre_producto}",
                ]),
            'bodegas' => Bodega::query()
                ->orderBy('nombre_bodega')
                ->get(['id', 'nombre_bodega'])
                ->map(fn (Bodega $bodega): array => ['id' => $bodega->id, 'nombre' => $bodega->nombre_bodega]),
        ]);
    }

    /**
     * Registra el stock inicial de un producto en una bodega.
     */
    public function store(GuardarStockInicialRequest $request): RedirectResponse
    {
        StockInicial::create($request->validated());

        $this->avisarExito('Stock inicial registrado.');

        return to_route('gestion.stock-inicial.index');
    }

    /**
     * Cambia la cantidad de un stock inicial.
     */
    public function update(GuardarStockInicialRequest $request, StockInicial $stockInicial): RedirectResponse
    {
        $stockInicial->update($request->validated());

        $this->avisarExito('Stock inicial actualizado.');

        return to_route('gestion.stock-inicial.index');
    }

    /**
     * Elimina (de forma lógica) un stock inicial.
     */
    public function destroy(StockInicial $stockInicial): RedirectResponse
    {
        $stockInicial->delete();

        $this->avisarExito('Stock inicial eliminado.');

        return to_route('gestion.stock-inicial.index');
    }

    /**
     * Restaura un stock inicial eliminado.
     */
    public function restore(StockInicial $stockInicial): RedirectResponse
    {
        $stockInicial->restore();

        $this->avisarExito('Stock inicial restaurado.');

        return to_route('gestion.stock-inicial.index');
    }
}
