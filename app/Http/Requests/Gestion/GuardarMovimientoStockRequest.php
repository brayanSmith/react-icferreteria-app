<?php

namespace App\Http\Requests\Gestion;

use App\Models\DetalleStock;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la corrección de un movimiento ya registrado. El producto y la bodega
 * no cambian: identifican las existencias a las que pertenece.
 */
class GuardarMovimientoStockRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(DetalleStock::TIPOS)],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'descripcion' => ['required', 'string', 'max:500'],
        ];
    }
}
