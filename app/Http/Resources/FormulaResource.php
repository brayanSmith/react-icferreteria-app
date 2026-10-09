<?php

namespace App\Http\Resources;

use App\Models\DetalleFormula;
use App\Models\Formula;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Formula
 */
class FormulaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_formula' => $this->nombre_formula,
            'descripcion_formula' => $this->descripcion_formula,
            'detalles' => $this->whenLoaded('detalleFormulas', fn () => $this->detalleFormulas
                ->map(fn (DetalleFormula $detalle): array => [
                    'producto_id' => $detalle->producto_id,
                    'producto' => $detalle->producto
                        ? "{$detalle->producto->codigo_producto} - {$detalle->producto->nombre_producto}"
                        : 'Producto no disponible',
                    'medida' => $detalle->producto?->medida?->nombre_medida,
                    'cantidad_producto' => (float) $detalle->cantidad_producto,
                ])
                ->values()
                ->all()),
        ];
    }
}
