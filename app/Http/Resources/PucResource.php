<?php

namespace App\Http\Resources;

use App\Models\Puc;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Puc
 */
class PucResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'cuenta' => $this->cuenta,
            'subcuenta' => $this->subcuenta,
            'concepto' => $this->concepto,
            'descripcion' => $this->descripcion,
        ];
    }
}
