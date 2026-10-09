<?php

namespace App\Http\Resources;

use App\Actions\Archivos\GuardarArchivoPublico;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Cliente
 */
class ClienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'razon_social' => $this->razon_social,
            'representante_legal' => $this->representante_legal,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'ciudad' => $this->ciudad,
            'direccion' => $this->direccion,
            'ruta_id' => $this->ruta_id,
            'comercial_id' => $this->comercial_id,
            'ruta' => $this->whenLoaded('ruta', fn () => $this->ruta?->ruta),
            'comercial' => $this->whenLoaded('comercial', fn () => $this->comercial?->name),
            'categoria' => $this->categoria,
            'sector' => $this->sector,
            'tipo_cliente' => $this->tipo_cliente,
            'retenedor_fuente' => $this->retenedor_fuente,
            'limite_credito' => (float) $this->limite_credito,
            'saldo_cartera' => (float) $this->saldo_total_pedidos_en_cartera,
            'activo' => (bool) $this->activo,
            'novedad' => $this->novedad,
            'rut_url' => app(GuardarArchivoPublico::class)->url($this->rut_imagen),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
