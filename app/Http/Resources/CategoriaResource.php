<?php

namespace App\Http\Resources;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Categoria
 */
class CategoriaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_categoria' => $this->nombre_categoria,
            'productos_count' => $this->whenCounted('productos'),
            'sub_categorias' => SubCategoriaResource::collection($this->whenLoaded('subCategorias')),
        ];
    }
}
