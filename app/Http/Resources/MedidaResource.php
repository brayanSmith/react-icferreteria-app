<?php

namespace App\Http\Resources;

use App\Models\Medida;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Medida
 */
class MedidaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_medida' => $this->nombre_medida,
            'tipo_medida' => $this->tipo_medida,
            'descripcion_medida' => $this->descripcion_medida,
            'productos_count' => $this->whenCounted('productos'),
        ];
    }
}
