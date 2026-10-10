<?php

namespace App\Http\Requests\Gestion;

use App\Models\DetalleStock;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida un movimiento nuevo de inventario: qué producto, en qué bodega, si
 * entra o sale, cuánto y por qué. Las existencias se crean solas la primera vez.
 */
class GuardarStockBodegaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            // Deben ser del tenant y no estar eliminados.
            'producto_id' => ['required', Rule::exists('productos', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'bodega_id' => ['required', Rule::exists('bodegas', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'tipo' => ['required', Rule::in(DetalleStock::TIPOS)],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'descripcion' => ['required', 'string', 'max:500'],
        ];
    }
}
