<?php

namespace App\Http\Resources;

use App\Models\SubCategoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SubCategoria
 */
class SubCategoriaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_sub_categoria' => $this->nombre_sub_categoria,
            'categoria_id' => $this->categoria_id,
            'productos_count' => $this->whenCounted('productos'),
        ];
    }
}
