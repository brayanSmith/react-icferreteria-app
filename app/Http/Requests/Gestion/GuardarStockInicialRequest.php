<?php

namespace App\Http\Requests\Gestion;

use App\Models\StockInicial;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Valida el alta y la edición de un stock inicial.
 *
 * Al crear se eligen producto, bodega y cantidad. Al editar solo cambia la
 * cantidad: el producto y la bodega identifican el registro y no se mueven.
 */
class GuardarStockInicialRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $cantidad = ['required', 'numeric', 'min:0', 'max:99999999.99'];

        if ($this->route('stock_inicial') !== null) {
            return ['cantidad' => $cantidad];
        }

        $tenantId = $this->user()->tenant_id;

        return [
            // Deben ser del tenant y no estar eliminados.
            'producto_id' => ['required', Rule::exists('productos', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'bodega_id' => ['required', Rule::exists('bodegas', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'cantidad' => $cantidad,
        ];
    }

    /**
     * Un producto solo tiene un stock inicial por bodega. Si ya existe (aunque
     * esté eliminado, porque la base lo sigue considerando), se explica qué hacer.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->route('stock_inicial') !== null || $validator->errors()->hasAny(['producto_id', 'bodega_id'])) {
                    return;
                }

                $existente = StockInicial::withTrashed()
                    ->where('producto_id', $this->integer('producto_id'))
                    ->where('bodega_id', $this->integer('bodega_id'))
                    ->first();

                if ($existente === null) {
                    return;
                }

                $validator->errors()->add('producto_id', $existente->trashed()
                    ? 'Este producto tiene un stock inicial eliminado en esta bodega; restáuralo en lugar de crear uno nuevo.'
                    : 'Este producto ya tiene un stock inicial en esta bodega; edítalo en lugar de crear otro.');
            },
        ];
    }
}
