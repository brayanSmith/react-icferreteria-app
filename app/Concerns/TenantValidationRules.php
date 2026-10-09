<?php

namespace App\Concerns;

use App\Models\Tenant;
use App\Rules\UniqueConPapelera;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

trait TenantValidationRules
{
    /**
     * Reglas compartidas por la creación y la edición de un tenant.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function tenantRules(?Tenant $tenant = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                new UniqueConPapelera(Tenant::class, 'slug', $tenant?->id),
            ],
            'activo' => ['boolean'],
        ];
    }

    /**
     * Normaliza la entrada: el slug se deriva del nombre si no se envía y
     * "activo" llega como booleano (un checkbox sin marcar no se envía).
     */
    protected function normalizarEntradaTenant(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('nombre'))),
            'activo' => $this->boolean('activo'),
        ]);
    }
}
