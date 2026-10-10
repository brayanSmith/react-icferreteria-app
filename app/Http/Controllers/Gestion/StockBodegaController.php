<?php

namespace App\Http\Controllers\Gestion;

use App\Actions\Importaciones\PanelDeImportacion;
use App\Actions\Stock\MoverStock;
use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarStockBodegaRequest;
use App\Http\Resources\DetalleStockResource;
use App\Http\Resources\StockBodegaResource;
use App\Models\Bodega;
use App\Models\Producto;
use App\Models\StockBodega;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Stock por bodega: las existencias actuales de cada producto en cada bodega
 * y el historial de movimientos (ingresos y salidas) que las explica.
 *
 * Las existencias no se editan a mano: se mueven registrando ingresos y
 * salidas (ver MoverStock), así el resumen siempre coincide con el historial.
 * Por eso aquí no se elimina un stock; se corrigen o anulan sus movimientos
 * desde el detalle (ver MovimientoStockController).
 */
class StockBodegaController extends Controller implements HasMiddleware
{
    use EsCrudDeGestion;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return self::permisosCrud(Modulo::StockBodegas, accionesDeVer: ['show']);
    }

    /**
     * Lista todas las existencias: la búsqueda, el filtro por bodega, el orden
     * y la paginación los hace el navegador. Incluye los productos y bodegas
     * disponibles para registrar un movimiento.
     */
    public function index(): Response
    {
        $stocks = StockBodega::query()
            ->with($this->relacionesParaMostrar())
            ->latest('updated_at')
            ->get();

        return Inertia::render('gestion/stock-bodegas/index', [
            'stocks' => StockBodegaResource::collection($stocks),
            ...PanelDeImportacion::props(['movimientos_stock']),
            ...$this->opcionesDelFormulario(),
        ]);
    }

    /**
     * Existencias de un producto en una bodega, con su historial de movimientos.
     */
    public function show(StockBodega $stockBodega): Response
    {
        $stockBodega->load($this->relacionesParaMostrar());

        $movimientos = $stockBodega->detalles()
            ->with('usuario:id,name')
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('gestion/stock-bodegas/show', [
            'stock' => new StockBodegaResource($stockBodega),
            'movimientos' => DetalleStockResource::collection($movimientos),
        ]);
    }

    /**
     * Registra un ingreso o una salida de un producto en una bodega. Si es el
     * primer movimiento de ese producto en esa bodega, se crean sus existencias.
     */
    public function store(GuardarStockBodegaRequest $request, MoverStock $moverStock): RedirectResponse
    {
        $datos = $request->validated();

        $moverStock->registrar(
            productoId: (int) $datos['producto_id'],
            bodegaId: (int) $datos['bodega_id'],
            usuarioId: $request->user()->id,
            tipo: $datos['tipo'],
            cantidad: (float) $datos['cantidad'],
            descripcion: $datos['descripcion'],
        );

        $this->avisarExito('Movimiento registrado.');

        // Vuelve a donde estaba (el listado con sus filtros o el detalle de un stock).
        return redirect()->back(fallback: route('gestion.stock-bodegas.index'));
    }

    /**
     * Producto y bodega de cada stock. Un producto o una bodega eliminados
     * siguen mostrándose, porque el historial no debe perder su nombre.
     *
     * @return array<string, mixed>
     */
    private function relacionesParaMostrar(): array
    {
        return [
            'producto' => fn ($query) => $query->withTrashed()
                ->select(['id', 'codigo_producto', 'nombre_producto', 'alerta_producto', 'medida_id'])
                ->with('medida:id,nombre_medida'),
            'bodega' => fn ($query) => $query->withTrashed()->select(['id', 'nombre_bodega']),
        ];
    }

    /**
     * Productos y bodegas para los selectores del formulario.
     *
     * @return array<string, mixed>
     */
    private function opcionesDelFormulario(): array
    {
        return [
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
        ];
    }
}
