<?php

namespace App\Http\Controllers\Gestion;

use App\Actions\Importaciones\PanelDeImportacion;
use App\Actions\Stock\SincronizarStockInicial;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Stock inicial: la cantidad con la que arranca cada producto en cada bodega.
 *
 * Se lista con búsqueda por producto y paginación, se elimina de forma lógica y
 * se puede restaurar. Al crear se elige producto y bodega; al editar solo se
 * cambia la cantidad.
 *
 * Cada stock inicial genera un ingreso en los movimientos de la bodega (ver
 * MoverStock) y lo sigue: si se corrige la cantidad, se corrige ese movimiento;
 * si se elimina, se anula; si se restaura, se vuelve a registrar. Como el stock
 * nunca puede quedar en negativo, no se puede reducir ni eliminar un stock
 * inicial cuya mercancía ya salió.
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
     * Lista todos los stocks iniciales, eliminados incluidos: la búsqueda, el
     * orden, la paginación y el filtro de eliminados los hace el navegador.
     * Incluye los productos y bodegas disponibles para el formulario de alta.
     */
    public function index(): Response
    {
        $stocks = StockInicial::query()
            // Un producto eliminado sigue mostrándose en el historial.
            ->with([
                'producto' => fn ($query) => $query->withTrashed()->select(['id', 'codigo_producto', 'nombre_producto']),
                'bodega' => fn ($query) => $query->withTrashed()->select(['id', 'nombre_bodega']),
            ])
            ->withTrashed()
            ->latest()
            ->get();

        return Inertia::render('gestion/stock-inicial/index', [
            'stocks' => StockInicialResource::collection($stocks),
            ...PanelDeImportacion::props(['stock_inicial']),
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
    public function store(GuardarStockInicialRequest $request, SincronizarStockInicial $sincronizarStock): RedirectResponse
    {
        DB::transaction(function () use ($request, $sincronizarStock): void {
            $sincronizarStock->handle(StockInicial::create($request->validated()), $request->user()->id);
        });

        $this->avisarExito('Stock inicial registrado.');

        return to_route('gestion.stock-inicial.index');
    }

    /**
     * Cambia la cantidad de un stock inicial.
     */
    public function update(GuardarStockInicialRequest $request, StockInicial $stockInicial, SincronizarStockInicial $sincronizarStock): RedirectResponse
    {
        // Si el movimiento se rechaza (dejaría el stock en negativo), la cantidad no cambia.
        DB::transaction(function () use ($request, $stockInicial, $sincronizarStock): void {
            $stockInicial->update($request->validated());

            $sincronizarStock->handle($stockInicial, $request->user()->id);
        });

        $this->avisarExito('Stock inicial actualizado.');

        return to_route('gestion.stock-inicial.index');
    }

    /**
     * Elimina (de forma lógica) un stock inicial.
     */
    public function destroy(Request $request, StockInicial $stockInicial, SincronizarStockInicial $sincronizarStock): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $stockInicial, $sincronizarStock): void {
                $stockInicial->delete();

                $sincronizarStock->handle($stockInicial, $request->user()->id);
            });
        } catch (ValidationException) {
            return $this->rechazarEliminacion(
                'No puedes eliminar este stock inicial: esa mercancía ya tuvo salidas y el stock quedaría en negativo.',
                'gestion.stock-inicial.index',
            );
        }

        $this->avisarExito('Stock inicial eliminado.');

        return to_route('gestion.stock-inicial.index');
    }

    /**
     * Restaura un stock inicial eliminado.
     */
    public function restore(Request $request, StockInicial $stockInicial, SincronizarStockInicial $sincronizarStock): RedirectResponse
    {
        DB::transaction(function () use ($request, $stockInicial, $sincronizarStock): void {
            $stockInicial->restore();

            $sincronizarStock->handle($stockInicial, $request->user()->id);
        });

        $this->avisarExito('Stock inicial restaurado.');

        return to_route('gestion.stock-inicial.index');
    }
}
