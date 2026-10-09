<?php

namespace App\Http\Requests\Gestion;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el alta y la edición de una ruta de reparto.
 */
class GuardarRutaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // La base no exige unicidad aquí, pero dos rutas con el mismo nombre confunden al repartir.
            'ruta' => [
                'required',
                'string',
                'max:100',
                Rule::unique('rutas', 'ruta')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($this->route('ruta')),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}
