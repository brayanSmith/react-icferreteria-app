<?php

namespace App\Http\Requests\Gestion;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el archivo CSV que se sube para importar.
 */
class SubirImportacionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo.required' => 'Selecciona un archivo CSV.',
            'archivo.mimes' => 'El archivo debe ser un CSV (.csv o .txt).',
            'archivo.max' => 'El archivo no puede pesar más de 20 MB.',
        ];
    }
}
