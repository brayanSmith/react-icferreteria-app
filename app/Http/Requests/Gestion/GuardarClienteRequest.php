<?php

namespace App\Http\Requests\Gestion;

use App\Models\Cliente;
use App\Rules\UniqueConPapelera;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el alta y la edición de un cliente, incluido su RUT adjunto.
 *
 * La ruta y el comercial se comprueban contra el tenant del usuario, para que
 * no se pueda enlazar un cliente con datos de otra empresa.
 */
class GuardarClienteRequest extends FormRequest
{
    public const TIPOS_DOCUMENTO = ['NIT', 'CC', 'CE', 'TI', 'PASAPORTE', 'OTRO'];

    public const TIPOS_CLIENTE = ['ELECTRONICO', 'REMISIONADO'];

    public const RETENEDOR_FUENTE = ['SI', 'NO'];

    /**
     * Los casilleros sin marcar no llegan en un formulario HTML y el límite de
     * crédito vacío significa cero.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'activo' => $this->boolean('activo'),
            'quitar_rut' => $this->boolean('quitar_rut'),
            'limite_credito' => $this->input('limite_credito') ?: 0,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        $clienteId = $this->route('cliente')?->id;

        return [
            'tipo_documento' => ['required', Rule::in(self::TIPOS_DOCUMENTO)],
            // Los clientes se eliminan de forma lógica: el documento y el correo siguen reservados hasta restaurarlo.
            'numero_documento' => ['required', 'string', 'max:50', new UniqueConPapelera(Cliente::class, 'numero_documento', $clienteId)],
            'razon_social' => ['required', 'string', 'max:255'],
            'representante_legal' => ['nullable', 'string', 'max:255'],

            'email' => ['nullable', 'email', 'max:255', new UniqueConPapelera(Cliente::class, 'email', $clienteId)],
            'telefono' => ['nullable', 'string', 'max:50'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:255'],

            'ruta_id' => ['nullable', Rule::exists('rutas', 'id')->where('tenant_id', $tenantId)],
            'comercial_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'categoria' => ['nullable', 'string', 'max:100'],
            'sector' => ['nullable', 'string', 'max:100'],

            'tipo_cliente' => ['required', Rule::in(self::TIPOS_CLIENTE)],
            'retenedor_fuente' => ['required', Rule::in(self::RETENEDOR_FUENTE)],
            'limite_credito' => ['numeric', 'min:0', 'max:9999999999'],
            'activo' => ['boolean'],
            'novedad' => ['nullable', 'string', 'max:255'],

            'rut' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:4096'],
            'quitar_rut' => ['boolean'],
        ];
    }
}
