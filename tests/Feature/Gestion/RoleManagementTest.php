<?php

namespace Tests\Feature\Gestion;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararGestion();
        $this->actingAs($this->administradorDe($this->tenant));
    }

    public function test_el_administrador_crea_un_rol_con_permisos_del_catalogo(): void
    {
        $this->post(route('gestion.roles.store'), [
            'name' => 'Vendedor',
            'permissions' => ['pedidos.ver', 'pedidos.crear'],
        ])->assertRedirect(route('gestion.roles.index'));

        $rol = Role::where('name', 'Vendedor')->firstOrFail();

        $this->assertSame($this->tenant->id, $rol->tenant_id);
        $this->assertEqualsCanonicalizing(['pedidos.ver', 'pedidos.crear'], $rol->permissions->pluck('name')->all());
    }

    public function test_rechaza_permisos_que_no_estan_en_el_catalogo(): void
    {
        $this->post(route('gestion.roles.store'), [
            'name' => 'Hacker',
            'permissions' => ['pedidos.ver', 'sistema.destruir'],
        ])->assertSessionHasErrors('permissions.1');

        $this->assertDatabaseMissing('roles', ['name' => 'Hacker']);
    }

    public function test_el_nombre_es_unico_dentro_del_tenant_pero_repetible_en_otro(): void
    {
        $this->crearRol($this->tenant, 'Vendedor');

        $this->post(route('gestion.roles.store'), ['name' => 'Vendedor'])
            ->assertSessionHasErrors('name');

        $otroTenant = Tenant::factory()->create();
        $this->actingAs($this->administradorDe($otroTenant));

        $this->post(route('gestion.roles.store'), ['name' => 'Vendedor'])
            ->assertSessionHasNoErrors();
    }

    public function test_actualiza_el_nombre_y_reemplaza_los_permisos(): void
    {
        $rol = $this->crearRol($this->tenant, 'Vendedor', ['pedidos.ver', 'pedidos.crear']);

        $this->put(route('gestion.roles.update', $rol), [
            'name' => 'Vendedor Senior',
            'permissions' => ['pedidos.ver', 'clientes.ver'],
        ])->assertRedirect(route('gestion.roles.index'));

        $rol->refresh();
        $this->assertSame('Vendedor Senior', $rol->name);
        $this->assertEqualsCanonicalizing(['pedidos.ver', 'clientes.ver'], $rol->permissions->pluck('name')->all());
    }

    public function test_puede_conservar_su_propio_nombre_al_actualizar(): void
    {
        $rol = $this->crearRol($this->tenant, 'Vendedor');

        $this->put(route('gestion.roles.update', $rol), ['name' => 'Vendedor', 'permissions' => []])
            ->assertSessionHasNoErrors();
    }

    public function test_elimina_un_rol(): void
    {
        $rol = $this->crearRol($this->tenant, 'Temporal');

        $this->delete(route('gestion.roles.destroy', $rol))
            ->assertRedirect(route('gestion.roles.index'));

        $this->assertModelMissing($rol);
    }

    public function test_el_listado_solo_muestra_los_roles_de_su_tenant(): void
    {
        $this->crearRol($this->tenant, 'Propio', ['pedidos.ver']);
        $this->crearRol(Tenant::factory()->create(), 'Ajeno');
        $this->usarTenantParaPermisos($this->tenant);

        $this->get(route('gestion.roles.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gestion/roles/index')
                ->has('roles.data', 1)
                ->where('roles.data.0.name', 'Propio')
                ->where('roles.data.0.permisos', ['pedidos.ver'])
                ->has('catalogo'));
    }

    public function test_no_edita_ni_elimina_roles_de_otro_tenant(): void
    {
        $ajeno = $this->crearRol(Tenant::factory()->create(), 'Ajeno');
        $this->usarTenantParaPermisos($this->tenant);

        $this->put(route('gestion.roles.update', $ajeno), ['name' => 'Robado'])->assertNotFound();
        $this->delete(route('gestion.roles.destroy', $ajeno))->assertNotFound();

        $this->assertDatabaseHas('roles', ['id' => $ajeno->id, 'name' => 'Ajeno']);
    }
}
