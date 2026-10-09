<?php

namespace App\Actions\Tenants;

use App\Models\User;
use Spatie\Permission\Models\Role;

class CreateTenantUser
{
    /**
     * Crea un usuario de personal del tenant, con los roles indicados.
     *
     * Nace con el correo verificado y la contraseña como temporal: deberá
     * cambiarla en su primer ingreso.
     *
     * @param  array{name: string, email: string, n_documento: string, password: string, cod_vendedor?: string|null, activo?: bool, roles?: list<int>}  $datos
     */
    public function handle(int $tenantId, array $datos): User
    {
        $usuario = new User([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'n_documento' => $datos['n_documento'],
            'password' => $datos['password'],
            'cod_vendedor' => $datos['cod_vendedor'] ?? null,
            'activo' => $datos['activo'] ?? true,
        ]);

        $usuario->forceFill([
            'tenant_id' => $tenantId,
            'es_admin' => false,
            'debe_cambiar_password' => true,
            'email_verified_at' => now(),
        ])->save();

        $usuario->syncRoles(Role::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $datos['roles'] ?? [])
            ->get());

        return $usuario;
    }
}
