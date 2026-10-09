<?php

namespace App\Http\Requests\Gestion;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el alta y la edición de una subcategoría. La categoría viene en la
 * ruta, así que ya está limitada al tenant del usuario.
 */
class GuardarSubCategoriaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // El nombre solo debe ser único dentro de su categoría.
            'nombre_sub_categoria' => [
                'required',
                'string',
                'max:100',
                Rule::unique('sub_categorias', 'nombre_sub_categoria')
                    ->where('categoria_id', $this->route('categoria')->id)
                    ->ignore($this->route('sub_categoria')),
            ],
        ];
    }
}
