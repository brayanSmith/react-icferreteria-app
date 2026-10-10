<?php

namespace App\Http\Requests\Gestion;

use App\Actions\Importaciones\Registro;
use App\Models\Importacion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Valida la relación entre las columnas del archivo y las del sistema.
 *
 * `mapeo[clave]` trae la posición de la columna del archivo (o vacío si esa
 * columna no se importa), y `modo` es crear, actualizar o ambos (con ID actualiza,
 * sin ID crea).
 */
class ConfirmarImportacionRequest extends FormRequest
{
    /**
     * Confirmar exige poder crear en el módulo de la importación y, si el archivo
     * modifica registros existentes (actualizar o crear y actualizar), también poder editar.
     */
    public function authorize(): bool
    {
        $importador = Registro::importador($this->route('importacion')->modulo);

        if ($importador === null || ! Gate::allows("{$importador->modulo()->value}.crear")) {
            return false;
        }

        return $this->input('modo') === Importacion::MODO_CREAR
            || Gate::allows("{$importador->modulo()->value}.editar");
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $totalColumnas = count($this->route('importacion')->encabezados ?? []);

        return [
            'modo' => ['required', Rule::in(Registro::importador($this->route('importacion')->modulo)?->modosPermitidos() ?? [Importacion::MODO_CREAR])],
            'mapeo' => ['required', 'array'],
            'mapeo.*' => ['nullable', 'integer', 'min:0', 'max:'.max(0, $totalColumnas - 1)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['mapeo.*.max' => 'Esa columna no existe en el archivo.'];
    }

    /**
     * Cada columna obligatoria (según el modo elegido) debe tener una columna del archivo.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validador): void {
            $importador = Registro::importador($this->route('importacion')->modulo);

            if ($importador === null) {
                return;
            }

            foreach ($importador->columnasRequeridas($this->modo()) as $clave) {
                if (($this->mapeo()[$clave] ?? null) === null) {
                    $validador->errors()->add("mapeo.{$clave}", 'Elige la columna de tu archivo que corresponde a '.$importador->columnas()[$clave].'.');
                }
            }
        }];
    }

    public function modo(): string
    {
        return $this->string('modo')->toString();
    }

    /**
     * Posición de la columna del archivo para cada columna del sistema (null si no se importa).
     *
     * @return array<string, int|null>
     */
    public function mapeo(): array
    {
        $importador = Registro::importador($this->route('importacion')->modulo);
        $enviado = $this->input('mapeo', []);
        $mapeo = [];

        foreach (array_keys($importador?->columnas() ?? []) as $clave) {
            $valor = $enviado[$clave] ?? null;
            $mapeo[$clave] = $valor === null || $valor === '' ? null : (int) $valor;
        }

        return $mapeo;
    }
}
