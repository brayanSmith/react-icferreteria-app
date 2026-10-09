<?php

namespace App\Http\Requests\Gestion;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el alta y la edición de una cuenta del plan de cuentas (PUC).
 */
class GuardarPucRequest extends FormRequest
{
    /**
     * Clases de cuenta del PUC (1 = Activo ... 9 = Cuentas de orden).
     */
    public const TIPOS = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'cuenta' => ['required', 'string', 'max:50'],
            // La subcuenta identifica la cuenta dentro del tenant; la base también lo exige.
            'subcuenta' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pucs', 'subcuenta')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($this->route('puc')),
            ],
            'concepto' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}
