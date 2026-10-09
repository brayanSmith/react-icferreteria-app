<?php

namespace App\Http\Requests\Gestion;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmpresaRequest extends FormRequest
{
    /**
     * Tipos de cuenta bancaria aceptados.
     */
    public const TIPOS_CUENTA = ['AHORRO', 'CORRIENTE'];

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mostrar_productos_sin_inventario' => $this->boolean('mostrar_productos_sin_inventario'),
            'quitar_logo' => $this->boolean('quitar_logo'),
            'quitar_icono' => $this->boolean('quitar_icono'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre_empresa' => ['required', 'string', 'max:255'],
            'nit_empresa' => ['nullable', 'string', 'max:50'],
            'email_empresa' => ['nullable', 'email', 'max:255'],
            'telefono_empresa' => ['nullable', 'string', 'max:50'],
            'direccion_empresa' => ['nullable', 'string', 'max:255'],
            'mostrar_productos_sin_inventario' => ['boolean'],

            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'icono' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg,ico', 'max:1024'],
            'quitar_logo' => ['boolean'],
            'quitar_icono' => ['boolean'],

            'cuentas_bancarias' => ['nullable', 'array', 'max:10'],
            'cuentas_bancarias.*.banco' => ['required', 'string', 'max:100'],
            'cuentas_bancarias.*.tipo' => ['required', Rule::in(self::TIPOS_CUENTA)],
            'cuentas_bancarias.*.numero' => ['required', 'string', 'max:50'],
            'cuentas_bancarias.*.titular' => ['nullable', 'string', 'max:255'],
        ];
    }
}
