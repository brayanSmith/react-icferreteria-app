<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait TenantUserValidationRules
{
    use TenantAdminValidationRules;

    /**
     * Reglas de un usuario del tenant del usuario autenticado: las credenciales
     * comunes más los datos propios del personal y los roles asignados.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function reglasUsuario(?int $ignorarUsuarioId = null, bool $exigirPassword = true): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            ...$this->reglasAdministrador(
                tenantId: $tenantId,
                ignorarUsuarioId: $ignorarUsuarioId,
                exigirPassword: $exigirPassword,
            ),
            'cod_vendedor' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('tenant_id', $tenantId)],
        ];
    }
}
