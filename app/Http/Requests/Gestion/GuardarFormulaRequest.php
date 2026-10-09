<?php

namespace App\Http\Requests\Gestion;

use App\Models\Producto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el alta y la edición de una fórmula junto con su detalle (los
 * ingredientes), que llegan en la misma petición para guardarse de una vez.
 *
 * Los productos se comprueban contra el tenant del usuario, para que una
 * fórmula no pueda usar productos de otra empresa.
 */
class GuardarFormulaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'nombre_formula' => [
                'required',
                'string',
                'max:255',
                Rule::unique('formulas', 'nombre_formula')
                    ->where('tenant_id', $tenantId)
                    ->ignore($this->route('formula')),
            ],
            'descripcion_formula' => ['nullable', 'string', 'max:2000'],

            'detalles' => ['required', 'array', 'min:1', 'max:100'],
            // "distinct": un producto no puede repetirse dentro de la misma fórmula.
            // Una fórmula es una receta: solo admite insumos, es decir, materia prima.
            'detalles.*.producto_id' => [
                'required',
                'distinct',
                Rule::exists('productos', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('categoria_producto', Producto::MATERIA_PRIMA)
                    ->whereNull('deleted_at'),
            ],
            'detalles.*.cantidad_producto' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'detalles.required' => 'Agrega al menos un ingrediente.',
            'detalles.min' => 'Agrega al menos un ingrediente.',
            'detalles.*.producto_id.required' => 'Elige un producto.',
            'detalles.*.producto_id.distinct' => 'Este producto ya está en la fórmula.',
            'detalles.*.producto_id.exists' => 'Elige un producto de tipo materia prima.',
            'detalles.*.cantidad_producto.required' => 'Indica la cantidad.',
            'detalles.*.cantidad_producto.gt' => 'La cantidad debe ser mayor que cero.',
        ];
    }
}
