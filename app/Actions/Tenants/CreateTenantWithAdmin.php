<?php

namespace App\Actions\Tenants;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class CreateTenantWithAdmin
{
    public function __construct(private CreateTenantAdmin $crearAdministrador) {}

    /**
     * Crea un tenant junto con sus administradores. Todo ocurre en una
     * transacción: si falla un usuario no queda un tenant a medias.
     *
     * @param  array{nombre: string, slug: string, activo: bool}  $datosTenant
     * @param  list<array{name: string, email: string, n_documento: string, password: string}>  $administradores
     */
    public function handle(array $datosTenant, array $administradores): Tenant
    {
        return DB::transaction(function () use ($datosTenant, $administradores): Tenant {
            $tenant = Tenant::create($datosTenant);

            foreach ($administradores as $datosAdministrador) {
                $this->crearAdministrador->handle($tenant, $datosAdministrador);
            }

            return $tenant;
        });
    }
}
