<?php

namespace Tests\Feature\Gestion;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Utilidades comunes de las pruebas de gestión (roles y usuarios por tenant).
 */
trait PreparaGestion
{
    protected Tenant $tenant;

    protected function prepararGestion(): void
    {
        $this->seed(PermisosSeeder::class);
        $this->tenant = Tenant::factory()->create();
    }

    protected function administradorDe(Tenant $tenant): User
    {
        return User::factory()->create(['tenant_id' => $tenant->id, 'es_admin' => true]);
    }

    /**
     * @param  list<string>  $permisos
     */
    protected function crearRol(Tenant $tenant, string $nombre, array $permisos = []): Role
    {
        $rol = Role::create(['name' => $nombre, 'guard_name' => 'web', 'tenant_id' => $tenant->id]);

        $this->usarTenantParaPermisos($tenant);
        $rol->syncPermissions($permisos);

        return $rol;
    }

    /**
     * Personal del tenant con un rol que concede los permisos indicados.
     *
     * @param  list<string>  $permisos
     */
    protected function personalConPermisos(Tenant $tenant, array $permisos): User
    {
        $usuario = User::factory()->create(['tenant_id' => $tenant->id, 'es_admin' => false]);

        $this->usarTenantParaPermisos($tenant);
        $usuario->assignRole($this->crearRol($tenant, 'Rol '.fake()->unique()->word(), $permisos));

        return $usuario;
    }

    protected function usarTenantParaPermisos(Tenant $tenant): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    }
}
