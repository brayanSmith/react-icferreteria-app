<?php

namespace App\Http\Controllers\Gestion;

use App\Actions\Stock\MoverStock;
use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarMovimientoStockRequest;
use App\Models\DetalleStock;
use App\Models\StockBodega;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;

/**
 * Corrección y anulación de los movimientos de inventario registrados a mano
 * en el detalle de un stock por bodega. Los movimientos que generó otro
 * documento (un pedido, una compra...) se corrigen en ese documento, no aquí.
 */
class MovimientoStockController extends Controller implements HasMiddleware
{
    use EsCrudDeGestion;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return self::permisosCrud(Modulo::StockBodegas);
    }

    /**
     * Cambia el tipo, la cantidad o la descripción de un movimiento y ajusta las existencias.
     */
    public function update(GuardarMovimientoStockRequest $request, StockBodega $stockBodega, DetalleStock $movimiento, MoverStock $moverStock): RedirectResponse
    {
        $this->asegurarMovimientoEditable($stockBodega, $movimiento);

        $datos = $request->validated();

        $moverStock->corregir($movimiento, $datos['tipo'], (float) $datos['cantidad'], $datos['descripcion']);

        $this->avisarExito('Movimiento actualizado.');

        return to_route('gestion.stock-bodegas.show', $stockBodega);
    }

    /**
     * Anula un movimiento y deshace su efecto en las existencias, salvo que
     * eso las deje en negativo.
     */
    public function destroy(StockBodega $stockBodega, DetalleStock $movimiento, MoverStock $moverStock): RedirectResponse
    {
        $this->asegurarMovimientoEditable($stockBodega, $movimiento);

        try {
            $moverStock->anular($movimiento);
        } catch (ValidationException) {
            return $this->rechazarEliminacion(
                'No puedes anular este movimiento: dejaría el stock en negativo porque esa mercancía ya salió.',
                'gestion.stock-bodegas.index',
            );
        }

        $this->avisarExito('Movimiento anulado.');

        return to_route('gestion.stock-bodegas.show', $stockBodega);
    }

    /**
     * El movimiento debe ser de este stock (si no, se trata como inexistente)
     * y haberse registrado a mano aquí.
     */
    private function asegurarMovimientoEditable(StockBodega $stockBodega, DetalleStock $movimiento): void
    {
        abort_unless($movimiento->stock_bodega_id === $stockBodega->id, 404);
        abort_unless(
            $movimiento->esManual(),
            403,
            'Este movimiento lo generó otro documento y se corrige allí.',
        );
    }
}
