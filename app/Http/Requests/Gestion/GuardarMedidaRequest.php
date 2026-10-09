<?php

namespace App\Http\Requests\Gestion;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el alta y la edición de una unidad de medida.
 */
class GuardarMedidaRequest extends FormRequest
{
    /**
     * Magnitudes que puede medir una unidad.
     */
    public const TIPOS = ['LONGITUD', 'PESO', 'VOLUMEN', 'CANTIDAD'];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre_medida' => [
                'required',
                'string',
                'max:100',
                Rule::unique('medidas', 'nombre_medida')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($this->route('medida')),
            ],
            'tipo_medida' => ['required', Rule::in(self::TIPOS)],
            'descripcion_medida' => ['nullable', 'string', 'max:255'],
        ];
    }
}
