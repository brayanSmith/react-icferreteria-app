<?php

namespace App\Concerns;

use App\Models\User;
use App\Rules\UniqueConPapelera;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait TenantAdminValidationRules
{
    /**
     * Reglas de los datos de un administrador de tenant.
     *
     * Con `$prefijo` (por ejemplo "admins.*.") sirve para validar una lista de
     * administradores enviada junto con el alta del tenant. `$tenantId` limita la
     * unicidad del documento a un tenant que ya existe y `$ignorarUsuarioId`
     * excluye al administrador que se está editando. Con `$exigirPassword` en
     * falso la contraseña es opcional (se conserva la actual si no se envía).
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function reglasAdministrador(
        string $prefijo = '',
        ?int $tenantId = null,
        ?int $ignorarUsuarioId = null,
        bool $exigirPassword = true,
    ): array {
        $enLista = $prefijo !== '';

        $unicidadDocumento = $tenantId === null
            ? []
            : [Rule::unique('users', 'n_documento')->where('tenant_id', $tenantId)->ignore($ignorarUsuarioId)];

        return [
            "{$prefijo}name" => ['required', 'string', 'max:255'],
            "{$prefijo}email" => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                ...($enLista ? ['distinct'] : []),
                new UniqueConPapelera(User::class, 'email', $ignorarUsuarioId),
            ],
            "{$prefijo}n_documento" => [
                'required',
                'string',
                'max:255',
                ...($enLista ? ['distinct'] : []),
                ...$unicidadDocumento,
            ],
            "{$prefijo}password" => [
                $exigirPassword ? 'required' : 'nullable',
                'string',
                Password::default(),
            ],
        ];
    }
}
