<?php

namespace App\Actions\Tenants;

use App\Models\Tenant;
use App\Models\User;

class CreateTenantAdmin
{
    /**
     * Crea un administrador para el tenant.
     *
     * Nace con el correo verificado y con la obligación de cambiar la
     * contraseña temporal en su primer ingreso.
     *
     * @param  array{name: string, email: string, n_documento: string, password: string}  $datos
     */
    public function handle(Tenant $tenant, array $datos): User
    {
        $administrador = new User($datos);

        $administrador->forceFill([
            'tenant_id' => $tenant->id,
            'es_admin' => true,
            'debe_cambiar_password' => true,
            'email_verified_at' => now(),
        ])->save();

        return $administrador;
    }
}
