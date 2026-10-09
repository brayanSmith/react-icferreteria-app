<?php

namespace App\Http\Requests\Gestion;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el alta y la edición de una categoría de productos.
 */
class GuardarCategoriaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre_categoria' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categorias', 'nombre_categoria')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($this->route('categoria')),
            ],
        ];
    }
}
