<?php

namespace App\Http\Resources;

use App\Models\Bodega;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Bodega
 */
class BodegaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_bodega' => $this->nombre_bodega,
            'ubicacion_bodega' => $this->ubicacion_bodega,
            'productos_count' => $this->whenCounted('productos'),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
