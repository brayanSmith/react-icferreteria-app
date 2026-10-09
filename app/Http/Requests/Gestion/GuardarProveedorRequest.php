<?php

namespace App\Http\Requests\Gestion;

use App\Models\Proveedor;
use App\Rules\UniqueConPapelera;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el alta y la edición de un proveedor, incluido su RUT adjunto.
 */
class GuardarProveedorRequest extends FormRequest
{
    public const TIPOS = ['REMISIONADO', 'ELECTRONICO'];

    public const CATEGORIAS = ['DECLARANTE', 'NO_DECLARANTE', 'RETENEDOR'];

    public const TIPOS_CUENTA = ['AHORRO', 'CORRIENTE'];

    /**
     * Normaliza lo que un formulario HTML no envía bien: los casilleros sin
     * marcar no llegan, y el valor del flete solo aplica si el proveedor cobra flete.
     */
    protected function prepareForValidation(): void
    {
        $cobraFlete = $this->boolean('flete');

        $this->merge([
            'flete' => $cobraFlete,
            'valor_flete' => $cobraFlete ? ($this->input('valor_flete') ?: 0) : 0,
            'quitar_rut' => $this->boolean('quitar_rut'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre_proveedor' => ['required', 'string', 'max:255'],
            'razon_social_proveedor' => ['nullable', 'string', 'max:255'],
            // Los proveedores se eliminan de forma lógica: el NIT sigue reservado hasta restaurarlo.
            'nit_proveedor' => [
                'required',
                'string',
                'max:50',
                new UniqueConPapelera(Proveedor::class, 'nit_proveedor', $this->route('proveedor')?->id),
            ],
            'tipo_proveedor' => ['required', Rule::in(self::TIPOS)],
            'categoria_proveedor' => ['required', Rule::in(self::CATEGORIAS)],
            'departamento_proveedor' => ['nullable', 'string', 'max:100'],
            'ciudad_proveedor' => ['nullable', 'string', 'max:100'],
            'direccion_proveedor' => ['nullable', 'string', 'max:255'],
            'telefono_proveedor' => ['nullable', 'string', 'max:50'],
            'banco_proveedor' => ['nullable', 'string', 'max:100'],
            'tipo_cuenta_proveedor' => ['nullable', Rule::in(self::TIPOS_CUENTA)],
            'numero_cuenta_proveedor' => ['nullable', 'string', 'max:50'],
            'convenio' => ['nullable', 'string', 'max:255'],
            'tiempo_respuesta' => ['nullable', 'string', 'max:255'],
            'fabricante' => ['nullable', 'string', 'max:255'],
            'flete' => ['boolean'],
            'valor_flete' => ['numeric', 'min:0', 'max:9999999999999'],

            'rut' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:4096'],
            'quitar_rut' => ['boolean'],
        ];
    }
}
