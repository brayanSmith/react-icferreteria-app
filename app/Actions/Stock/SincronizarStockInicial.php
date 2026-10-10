<?php

namespace App\Actions\Stock;

use App\Models\DetalleStock;
use App\Models\StockInicial;
use Illuminate\Validation\ValidationException;

/**
 * Hace que el inventario de la bodega refleje un stock inicial: un ingreso por
 * su cantidad mientras esté activo y ningún movimiento si está eliminado.
 *
 * Lo comparten la pantalla de Stock inicial y su importación, para que ambas
 * traten el inventario exactamente igual. Debe llamarse dentro de la misma
 * transacción que guarda el stock inicial.
 */
class SincronizarStockInicial
{
    public function __construct(private MoverStock $moverStock) {}

    /**
     * @throws ValidationException Si el ajuste dejaría el stock en negativo.
     */
    public function handle(StockInicial $stockInicial, int $usuarioId): void
    {
        $this->moverStock->sincronizar(
            origen: $stockInicial,
            linea: $stockInicial->trashed() ? null : [
                'producto_id' => $stockInicial->producto_id,
                'bodega_id' => $stockInicial->bodega_id,
                'tipo' => DetalleStock::INGRESO,
                'cantidad' => $stockInicial->cantidad,
            ],
            usuarioId: $usuarioId,
            descripcion: 'Stock inicial',
        );
    }
}
