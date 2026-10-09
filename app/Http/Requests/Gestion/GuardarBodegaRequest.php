<?php

namespace App\Http\Requests\Gestion;

use App\Models\Bodega;
use App\Rules\UniqueConPapelera;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el alta y la edición de una bodega.
 */
class GuardarBodegaRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Las bodegas se eliminan de forma lógica: el nombre sigue reservado hasta restaurarla.
            'nombre_bodega' => [
                'required',
                'string',
                'max:100',
                new UniqueConPapelera(Bodega::class, 'nombre_bodega', $this->route('bodega')?->id),
            ],
            'ubicacion_bodega' => ['nullable', 'string', 'max:255'],
        ];
    }
}
