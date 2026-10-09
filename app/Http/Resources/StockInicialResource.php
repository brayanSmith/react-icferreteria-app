<?php

namespace App\Http\Resources;

use App\Models\StockInicial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockInicial
 */
class StockInicialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'bodega_id' => $this->bodega_id,
            'codigo_producto' => $this->whenLoaded('producto', fn () => $this->producto?->codigo_producto),
            'nombre_producto' => $this->whenLoaded('producto', fn () => $this->producto?->nombre_producto),
            'bodega' => $this->whenLoaded('bodega', fn () => $this->bodega?->nombre_bodega),
            'cantidad' => (float) $this->cantidad,
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
