<?php

namespace App\Actions\Tenants;

use App\Models\User;

class UpdateTenantAdmin
{
    /**
     * Actualiza los datos de un administrador.
     *
     * Si llega una contraseña nueva se reemplaza y queda como temporal: el
     * administrador deberá cambiarla en su próximo ingreso. Sin contraseña se
     * conserva la actual.
     *
     * @param  array{name: string, email: string, n_documento: string, password?: string|null}  $datos
     */
    public function handle(User $administrador, array $datos): User
    {
        $administrador->fill([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'n_documento' => $datos['n_documento'],
        ]);

        if (filled($datos['password'] ?? null)) {
            $administrador->password = $datos['password'];
            $administrador->debe_cambiar_password = true;
        }

        $administrador->save();

        return $administrador;
    }
}
