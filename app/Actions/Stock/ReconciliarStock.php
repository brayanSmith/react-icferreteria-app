<?php

namespace App\Actions\Stock;

use App\Models\DetalleStock;
use App\Models\Scopes\TenantScope;
use App\Models\StockBodega;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Revisa que las existencias de cada bodega (StockBodega) coincidan con la
 * suma de sus movimientos (DetalleStock). Si alguien o algo movió el
 * inventario sin pasar por MoverStock, aquí aparece la diferencia.
 *
 * Trabaja sobre todas las empresas, o solo una, sin depender del usuario
 * autenticado: pensado para correrse desde la consola.
 */
class ReconciliarStock
{
    /**
     * Diferencias entre lo registrado en las existencias y lo que suman los
     * movimientos activos. Cada elemento es una existencia con al menos una
     * cifra descuadrada.
     *
     * @return list<array{stock_bodega_id: int, tenant_id: int, producto_id: int, bodega_id: int, entradas: float, entradas_reales: float, salidas: float, salidas_reales: float, stock: float, stock_real: float}>
     */
    public function revisar(?int $tenantId = null): array
    {
        $diferencias = [];

        $this->existencias($tenantId)->chunkById(500, function ($existencias) use (&$diferencias): void {
            foreach ($existencias as $existencia) {
                $entradasReales = round((float) ($existencia->entradas_reales ?? 0), 2);
                $salidasReales = round((float) ($existencia->salidas_reales ?? 0), 2);
                $stockReal = round($entradasReales - $salidasReales, 2);

                $cuadra = round((float) $existencia->entradas, 2) === $entradasReales
                    && round((float) $existencia->salidas, 2) === $salidasReales
                    && round((float) $existencia->stock, 2) === $stockReal;

                if ($cuadra) {
                    continue;
                }

                $diferencias[] = [
                    'stock_bodega_id' => $existencia->id,
                    'tenant_id' => $existencia->tenant_id,
                    'producto_id' => $existencia->producto_id,
                    'bodega_id' => $existencia->bodega_id,
                    'entradas' => (float) $existencia->entradas,
                    'entradas_reales' => $entradasReales,
                    'salidas' => (float) $existencia->salidas,
                    'salidas_reales' => $salidasReales,
                    'stock' => (float) $existencia->stock,
                    'stock_real' => $stockReal,
                ];
            }
        });

        return $diferencias;
    }

    /**
     * Deja las existencias con las cifras que suman sus movimientos. El stock
     * se recalcula solo al guardar.
     *
     * @param  list<array{stock_bodega_id: int, entradas_reales: float, salidas_reales: float}>  $diferencias
     */
    public function corregir(array $diferencias): int
    {
        foreach ($diferencias as $diferencia) {
            DB::transaction(function () use ($diferencia): void {
                StockBodega::withoutGlobalScopes()
                    ->withTrashed()
                    ->lockForUpdate()
                    ->findOrFail($diferencia['stock_bodega_id'])
                    ->forceFill([
                        'entradas' => $diferencia['entradas_reales'],
                        'salidas' => $diferencia['salidas_reales'],
                    ])
                    ->save();
            });
        }

        return count($diferencias);
    }

    /**
     * Movimientos activos cuyo producto no es el de las existencias a las que
     * están enlazados. No se pueden corregir automáticamente: alguien debe
     * decidir cuál de los dos es el correcto.
     *
     * @return list<array{movimiento_id: int, tenant_id: int, stock_bodega_id: int, producto_del_movimiento: int, producto_del_stock: int}>
     */
    public function movimientosDeOtroProducto(?int $tenantId = null): array
    {
        return DetalleStock::withoutGlobalScopes()
            ->whereNull('detalle_stocks.deleted_at')
            ->join('stock_bodegas', 'stock_bodegas.id', '=', 'detalle_stocks.stock_bodega_id')
            ->whereColumn('detalle_stocks.producto_id', '!=', 'stock_bodegas.producto_id')
            ->when($tenantId !== null, fn ($query) => $query->where('detalle_stocks.tenant_id', $tenantId))
            ->get([
                'detalle_stocks.id as movimiento_id',
                'detalle_stocks.tenant_id',
                'detalle_stocks.stock_bodega_id',
                'detalle_stocks.producto_id as producto_del_movimiento',
                'stock_bodegas.producto_id as producto_del_stock',
            ])
            ->map(fn ($fila): array => [
                'movimiento_id' => (int) $fila->movimiento_id,
                'tenant_id' => (int) $fila->tenant_id,
                'stock_bodega_id' => (int) $fila->stock_bodega_id,
                'producto_del_movimiento' => (int) $fila->producto_del_movimiento,
                'producto_del_stock' => (int) $fila->producto_del_stock,
            ])
            ->all();
    }

    /**
     * Existencias de todas las empresas (o de una), con lo que suman sus
     * movimientos activos.
     *
     * @return Builder<StockBodega>
     */
    private function existencias(?int $tenantId)
    {
        $movimientos = fn (string $tipo) => fn ($query) => $query
            ->withoutGlobalScope(TenantScope::class)
            ->where('tipo', $tipo);

        return StockBodega::withoutGlobalScopes()
            ->withTrashed()
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->withSum(['detalles as entradas_reales' => $movimientos(DetalleStock::INGRESO)], 'cantidad')
            ->withSum(['detalles as salidas_reales' => $movimientos(DetalleStock::SALIDA)], 'cantidad');
    }
}
