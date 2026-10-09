<?php

namespace App\Actions\Tenants;

use App\Models\User;
use Spatie\Permission\Models\Role;

class UpdateTenantUser
{
    /**
     * Actualiza un usuario de personal y reemplaza sus roles.
     *
     * Si llega una contraseña nueva se reemplaza y queda como temporal; sin
     * contraseña se conserva la actual.
     *
     * @param  array{name: string, email: string, n_documento: string, password?: string|null, cod_vendedor?: string|null, activo?: bool, roles?: list<int>}  $datos
     */
    public function handle(User $usuario, array $datos): User
    {
        $usuario->fill([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'n_documento' => $datos['n_documento'],
            'cod_vendedor' => $datos['cod_vendedor'] ?? null,
            'activo' => $datos['activo'] ?? $usuario->activo,
        ]);

        if (filled($datos['password'] ?? null)) {
            $usuario->password = $datos['password'];
            $usuario->debe_cambiar_password = true;
        }

        $usuario->save();

        $usuario->syncRoles(Role::query()
            ->where('tenant_id', $usuario->tenant_id)
            ->whereIn('id', $datos['roles'] ?? [])
            ->get());

        return $usuario;
    }
}
