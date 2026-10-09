<?php

namespace App\Http\Resources;

use App\Actions\Archivos\GuardarArchivoPublico;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Proveedor
 */
class ProveedorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_proveedor' => $this->nombre_proveedor,
            'razon_social_proveedor' => $this->razon_social_proveedor,
            'nit_proveedor' => $this->nit_proveedor,
            'tipo_proveedor' => $this->tipo_proveedor,
            'categoria_proveedor' => $this->categoria_proveedor,
            'departamento_proveedor' => $this->departamento_proveedor,
            'ciudad_proveedor' => $this->ciudad_proveedor,
            'direccion_proveedor' => $this->direccion_proveedor,
            'telefono_proveedor' => $this->telefono_proveedor,
            'banco_proveedor' => $this->banco_proveedor,
            'tipo_cuenta_proveedor' => $this->tipo_cuenta_proveedor,
            'numero_cuenta_proveedor' => $this->numero_cuenta_proveedor,
            'convenio' => $this->convenio,
            'tiempo_respuesta' => $this->tiempo_respuesta,
            'fabricante' => $this->fabricante,
            'flete' => (bool) $this->flete,
            'valor_flete' => (float) $this->valor_flete,
            'rut_url' => app(GuardarArchivoPublico::class)->url($this->rut_proveedor_imagen),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
