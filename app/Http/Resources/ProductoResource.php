<?php

namespace App\Http\Resources;

use App\Actions\Archivos\GuardarArchivoPublico;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Producto
 */
class ProductoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'categoria_producto' => $this->categoria_producto,
            'codigo_producto' => $this->codigo_producto,
            'nombre_producto' => $this->nombre_producto,
            'descripcion_producto' => $this->descripcion_producto,
            'costo_producto' => (float) $this->costo_producto,
            'valor_detal_producto' => (float) $this->valor_detal_producto,
            'valor_mayorista_producto' => (float) $this->valor_mayorista_producto,
            'valor_ferretero_producto' => (float) $this->valor_ferretero_producto,
            'valor_especial_producto' => (float) $this->valor_especial_producto,
            'iva_producto' => $this->iva_producto === null ? null : (float) $this->iva_producto,
            'medida_id' => $this->medida_id,
            'bodega_id' => $this->bodega_id,
            'categoria_id' => $this->categoria_id,
            'sub_categoria_id' => $this->sub_categoria_id,
            'medida' => $this->whenLoaded('medida', fn () => $this->medida?->nombre_medida),
            'bodega' => $this->whenLoaded('bodega', fn () => $this->bodega?->nombre_bodega),
            'categoria' => $this->whenLoaded('categoria', fn () => $this->categoria?->nombre_categoria),
            'sub_categoria' => $this->whenLoaded('subCategoria', fn () => $this->subCategoria?->nombre_sub_categoria),
            'stock' => $this->stock,
            'alerta_producto' => $this->alerta_producto,
            'activo' => (bool) $this->activo,
            'tipo_producto' => $this->tipo_producto,
            'peso_producto' => $this->peso_producto === null ? null : (float) $this->peso_producto,
            'ubicacion_producto' => $this->ubicacion_producto,
            'empaquetado_externo' => $this->empaquetado_externo,
            'empaquetado_interno' => $this->empaquetado_interno,
            'referencia_producto' => $this->referencia_producto,
            'codigo_cliente' => $this->codigo_cliente,
            'volumen_producto' => $this->volumen_producto,
            'tipo_compra' => $this->tipo_compra,
            'codigo_barra' => $this->codigo_barra,
            'marca' => $this->marca,
            'detalles_producto' => $this->detalles_producto,
            'codigo_importado' => $this->codigo_importado,
            'imagen_url' => app(GuardarArchivoPublico::class)->url($this->imagen_producto),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
