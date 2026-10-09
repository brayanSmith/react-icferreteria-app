<?php

namespace Tests\Feature\Gestion;

use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\Medida;
use App\Models\Producto;
use App\Models\SubCategoria;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Illuminate\Database\Eloquent\Model;
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

    /**
     * Crea un registro de un tenant concreto sin depender del usuario
     * autenticado (que es quien normalmente le asigna el tenant).
     *
     * @template T of Model
     *
     * @param  class-string<T>  $modelo
     * @param  array<string, mixed>  $atributos
     * @return T
     */
    protected function crearEn(string $modelo, Tenant $tenant, array $atributos): Model
    {
        $registro = new $modelo($atributos);
        $registro->forceFill(['tenant_id' => $tenant->id])->save();

        return $registro;
    }

    /**
     * Producto de un tenant enlazado a los catálogos básicos dados.
     *
     * @param  array{medida: Medida, bodega: Bodega, categoria: Categoria, subcategoria: SubCategoria}  $catalogos
     * @param  array<string, mixed>  $atributos
     */
    protected function crearProductoEn(Tenant $tenant, array $catalogos, array $atributos = []): Producto
    {
        return $this->crearEn(Producto::class, $tenant, [
            'codigo_producto' => 'P-001',
            'nombre_producto' => 'Martillo',
            'medida_id' => $catalogos['medida']->id,
            'categoria_id' => $catalogos['categoria']->id,
            'sub_categoria_id' => $catalogos['subcategoria']->id,
            ...$atributos,
        ]);
    }

    /**
     * Medida, bodega, categoría y subcategoría de un tenant: lo mínimo para
     * poder crear un producto.
     *
     * @return array{medida: Medida, bodega: Bodega, categoria: Categoria, subcategoria: SubCategoria}
     */
    protected function catalogosBasicosDe(Tenant $tenant): array
    {
        $categoria = $this->crearEn(Categoria::class, $tenant, ['nombre_categoria' => 'Herramientas']);

        return [
            'medida' => $this->crearEn(Medida::class, $tenant, ['nombre_medida' => 'Unidad', 'tipo_medida' => 'CANTIDAD']),
            'bodega' => $this->crearEn(Bodega::class, $tenant, ['nombre_bodega' => 'Principal']),
            'categoria' => $categoria,
            'subcategoria' => $categoria->subCategorias()->create(['nombre_sub_categoria' => 'Manuales']),
        ];
    }
}
