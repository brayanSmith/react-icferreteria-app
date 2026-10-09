<?php

namespace App\Concerns;

use App\Enums\Modulo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

trait RoleValidationRules
{
    /**
     * Reglas de un rol del tenant del usuario autenticado. Los permisos solo
     * pueden ser los del catálogo definido en código.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function reglasRol(?Role $rol = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->where('guard_name', 'web')
                    ->ignore($rol?->id),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(Modulo::permisos())],
        ];
    }
}
