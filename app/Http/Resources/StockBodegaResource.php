<?php

namespace App\Http\Resources;

use App\Models\StockBodega;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockBodega
 */
class StockBodegaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $alerta = (int) ($this->producto?->alerta_producto ?? 0);

        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'bodega_id' => $this->bodega_id,
            'codigo_producto' => $this->whenLoaded('producto', fn () => $this->producto?->codigo_producto),
            'nombre_producto' => $this->whenLoaded('producto', fn () => $this->producto?->nombre_producto),
            'medida' => $this->whenLoaded('producto', fn () => $this->producto?->medida?->nombre_medida),
            'bodega' => $this->whenLoaded('bodega', fn () => $this->bodega?->nombre_bodega),
            'entradas' => (float) $this->entradas,
            'salidas' => (float) $this->salidas,
            'stock' => (float) $this->stock,
            // Stock bajo: hay una alerta configurada en el producto y ya se alcanzó.
            'stock_bajo' => $alerta > 0 && (float) $this->stock <= $alerta,
        ];
    }
}
