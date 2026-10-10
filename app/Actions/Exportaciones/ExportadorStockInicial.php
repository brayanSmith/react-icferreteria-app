<?php

namespace App\Actions\Exportaciones;

use App\Enums\Modulo;
use App\Models\StockInicial;

/**
 * Exporta el stock inicial con las mismas columnas que acepta la importación,
 * así que el archivo se puede editar y volver a importar para actualizar
 * cantidades (el producto y la bodega lo identifican). Se agrega el nombre del
 * producto para que sea legible; la importación lo ignora.
 */
class ExportadorStockInicial extends Exportador
{
    public function modulo(): Modulo
    {
        return Modulo::StockInicial;
    }

    public function titulo(): string
    {
        return 'Stock inicial';
    }

    public function encabezados(): array
    {
        return ['Código del producto', 'Nombre del producto', 'Bodega', 'Cantidad'];
    }

    public function filas(): iterable
    {
        $stocks = StockInicial::query()
            ->with([
                'producto' => fn ($query) => $query->withTrashed()->select(['id', 'codigo_producto', 'nombre_producto']),
                'bodega' => fn ($query) => $query->withTrashed()->select(['id', 'nombre_bodega']),
            ])
            ->orderBy('id')
            ->lazy(500);

        foreach ($stocks as $stock) {
            yield [
                $stock->producto?->codigo_producto ?? '',
                $stock->producto?->nombre_producto ?? '',
                $stock->bodega?->nombre_bodega ?? '',
                $stock->cantidad,
            ];
        }
    }
}
