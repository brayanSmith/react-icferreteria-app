<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Valida unicidad en modelos con SoftDeletes distinguiendo dos casos:
 * el valor lo usa un registro activo, o lo usa uno borrado que se puede restaurar.
 *
 * Respeta los scopes globales del modelo (por ejemplo, el de tenant), de modo
 * que coincide con el alcance del índice único de la tabla.
 */
class UniqueConPapelera implements ValidationRule
{
    /**
     * @param  class-string<Model>  $modelo
     */
    public function __construct(
        private string $modelo,
        private string $columna,
        private int|string|null $ignorarId = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $existente = $this->modelo::withTrashed()
            ->where($this->columna, $value)
            ->when($this->ignorarId !== null, fn ($query) => $query->whereKeyNot($this->ignorarId))
            ->first();

        if ($existente === null) {
            return;
        }

        $fail($existente->trashed()
            ? 'Ya existe un registro eliminado con este valor en :attribute; restáuralo en lugar de crear uno nuevo.'
            : 'El valor de :attribute ya está en uso.');
    }
}
