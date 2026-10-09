<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TenantCrudTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoSuperAdmin(): User
    {
        $superAdmin = User::factory()->create(['tenant_id' => null, 'es_super_admin' => true]);
        $this->actingAs($superAdmin);

        return $superAdmin;
    }

    /**
     * Datos de alta de un tenant, con su administrador, sobrescribibles por prueba.
     *
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function datosTenant(array $sobrescribir = []): array
    {
        return [
            'nombre' => 'Ferreteria Nueva',
            'admins' => [[
                'name' => 'Ana Admin',
                'email' => 'ana@ferreteria.test',
                'n_documento' => '123456',
                'password' => 'Clave-Temporal-123',
            ]],
            ...$sobrescribir,
        ];
    }

    public function test_los_invitados_son_enviados_al_login(): void
    {
        $this->get(route('admin.tenants.index'))->assertRedirect(route('login'));
    }

    public function test_un_usuario_de_tenant_no_puede_administrar_tenants(): void
    {
        $this->actingAs(User::factory()->create(['tenant_id' => Tenant::factory()->create()->id]));

        $this->get(route('admin.tenants.index'))->assertForbidden();
        $this->post(route('admin.tenants.store'), ['nombre' => 'Intruso'])->assertForbidden();
        $this->assertDatabaseMissing('tenants', ['nombre' => 'Intruso']);
    }

    public function test_el_listado_muestra_los_tenants_y_filtra_por_texto(): void
    {
        $this->actuarComoSuperAdmin();
        Tenant::factory()->create(['nombre' => 'Ferreteria Norte', 'slug' => 'norte']);
        Tenant::factory()->create(['nombre' => 'Ferreteria Sur', 'slug' => 'sur']);

        $this->get(route('admin.tenants.index', ['buscar' => 'norte']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/tenants/index')
                ->has('tenants.data', 1)
                ->where('tenants.data.0.slug', 'norte')
                ->where('filtros.buscar', 'norte'));
    }

    public function test_el_listado_puede_mostrar_solo_los_eliminados(): void
    {
        $this->actuarComoSuperAdmin();
        Tenant::factory()->create(['slug' => 'activo']);
        Tenant::factory()->create(['slug' => 'borrado'])->delete();

        $this->get(route('admin.tenants.index', ['eliminados' => 1]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tenants.data', 1)
                ->where('tenants.data.0.slug', 'borrado'));
    }

    public function test_crea_un_tenant_y_deriva_el_slug_del_nombre(): void
    {
        $this->actuarComoSuperAdmin();

        $this->post(route('admin.tenants.store'), $this->datosTenant(['nombre' => 'Ferretería El Tornillo', 'activo' => 'on']))
            ->assertRedirect(route('admin.tenants.index'));

        $this->assertDatabaseHas('tenants', [
            'nombre' => 'Ferretería El Tornillo',
            'slug' => 'ferreteria-el-tornillo',
            'activo' => true,
        ]);
    }

    public function test_un_tenant_sin_marcar_activo_se_crea_inactivo(): void
    {
        $this->actuarComoSuperAdmin();

        $this->post(route('admin.tenants.store'), $this->datosTenant(['nombre' => 'Nueva']));

        $this->assertDatabaseHas('tenants', ['slug' => 'nueva', 'activo' => false]);
    }

    public function test_rechaza_un_slug_repetido_y_avisa_si_pertenece_a_uno_eliminado(): void
    {
        $this->actuarComoSuperAdmin();
        Tenant::factory()->create(['slug' => 'norte']);
        Tenant::factory()->create(['slug' => 'viejo'])->delete();

        $this->post(route('admin.tenants.store'), $this->datosTenant(['nombre' => 'Otro', 'slug' => 'norte']))
            ->assertSessionHasErrors(['slug' => 'El valor de slug ya está en uso.']);

        $this->post(route('admin.tenants.store'), $this->datosTenant(['nombre' => 'Otro', 'slug' => 'viejo']))
            ->assertSessionHasErrors('slug');
        $this->assertStringContainsString('restáuralo', session('errors')->first('slug'));
    }

    public function test_actualiza_un_tenant_sin_chocar_con_su_propio_slug(): void
    {
        $this->actuarComoSuperAdmin();
        $tenant = Tenant::factory()->create(['nombre' => 'Viejo', 'slug' => 'mi-slug']);

        $this->put(route('admin.tenants.update', $tenant), ['nombre' => 'Nuevo', 'slug' => 'mi-slug', 'activo' => '1'])
            ->assertRedirect(route('admin.tenants.index'));

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'nombre' => 'Nuevo', 'slug' => 'mi-slug']);
    }

    public function test_elimina_con_borrado_logico_y_restaura(): void
    {
        $this->actuarComoSuperAdmin();
        $tenant = Tenant::factory()->create();

        $this->delete(route('admin.tenants.destroy', $tenant))->assertRedirect(route('admin.tenants.index'));
        $this->assertSoftDeleted($tenant);

        $this->post(route('admin.restored-tenants.store', $tenant))->assertRedirect(route('admin.tenants.index'));
        $this->assertNotSoftDeleted($tenant);
    }

    public function test_no_se_puede_editar_un_tenant_eliminado(): void
    {
        $this->actuarComoSuperAdmin();
        $tenant = Tenant::factory()->create();
        $tenant->delete();

        $this->get(route('admin.tenants.edit', $tenant))->assertNotFound();
    }
}
