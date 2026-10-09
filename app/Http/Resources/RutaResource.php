<?php

namespace App\Http\Resources;

use App\Models\Ruta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ruta
 */
class RutaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ruta' => $this->ruta,
            'descripcion' => $this->descripcion,
            'clientes_count' => $this->whenCounted('clientes'),
        ];
    }
}
