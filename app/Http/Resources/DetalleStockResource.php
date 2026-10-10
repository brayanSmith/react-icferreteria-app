<?php

namespace App\Http\Resources;

use App\Models\DetalleStock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DetalleStock
 */
class DetalleStockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'cantidad' => (float) $this->cantidad,
            'origen' => $this->etiquetaOrigen(),
            'descripcion' => $this->descripcion,
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario?->name),
            'fecha' => $this->created_at?->toIso8601String(),
            // Solo los movimientos hechos a mano aquí se corrigen aquí; los demás
            // pertenecen al documento que los generó (pedido, compra...).
            'editable' => $this->esManual(),
        ];
    }
}
