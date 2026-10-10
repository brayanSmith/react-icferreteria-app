<?php

namespace App\Actions\Exportaciones;

use App\Enums\Modulo;
use App\Models\StockBodega;

/**
 * Exporta las existencias de cada producto en cada bodega: lo que ha entrado,
 * lo que ha salido y lo que queda. Es un informe para consultar o compartir; las
 * existencias solo cambian con movimientos, así que no se reimportan.
 */
class ExportadorStockBodegas extends Exportador
{
    public function modulo(): Modulo
    {
        return Modulo::StockBodegas;
    }

    public function titulo(): string
    {
        return 'Stock por bodega';
    }

    public function encabezados(): array
    {
        return ['Código del producto', 'Nombre del producto', 'Bodega', 'Medida', 'Entradas', 'Salidas', 'Stock'];
    }

    public function filas(): iterable
    {
        $existencias = StockBodega::query()
            ->with([
                'producto' => fn ($query) => $query->withTrashed()
                    ->select(['id', 'codigo_producto', 'nombre_producto', 'medida_id'])
                    ->with('medida:id,nombre_medida'),
                'bodega' => fn ($query) => $query->withTrashed()->select(['id', 'nombre_bodega']),
            ])
            ->orderBy('bodega_id')
            ->orderBy('id')
            ->lazy(500);

        foreach ($existencias as $existencia) {
            yield [
                $existencia->producto?->codigo_producto ?? '',
                $existencia->producto?->nombre_producto ?? '',
                $existencia->bodega?->nombre_bodega ?? '',
                $existencia->producto?->medida?->nombre_medida ?? '',
                $existencia->entradas,
                $existencia->salidas,
                $existencia->stock,
            ];
        }
    }
}
