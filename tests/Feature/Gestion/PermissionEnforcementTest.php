<?php

namespace Tests\Feature\Gestion;

use App\Enums\Modulo;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionEnforcementTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararGestion();
    }

    public function test_el_personal_sin_permisos_no_entra(): void
    {
        $this->actingAs(User::factory()->create(['tenant_id' => $this->tenant->id]));

        $this->get(route('gestion.roles.index'))->assertForbidden();
        $this->get(route('gestion.usuarios.index'))->assertForbidden();
    }

    public function test_cada_permiso_habilita_solo_su_accion(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['roles.ver']));

        $this->get(route('gestion.roles.index'))->assertOk();
        $this->post(route('gestion.roles.store'), ['name' => 'Nuevo'])->assertForbidden();
        $this->get(route('gestion.usuarios.index'))->assertForbidden();
    }

    public function test_el_administrador_tiene_acceso_total_sin_depender_de_roles(): void
    {
        $this->actingAs($this->administradorDe($this->tenant));

        $this->get(route('gestion.roles.index'))->assertOk();
        $this->get(route('gestion.usuarios.index'))->assertOk();
        $this->post(route('gestion.roles.store'), ['name' => 'Nuevo'])->assertRedirect();
    }

    public function test_el_super_admin_sin_tenant_no_entra_a_la_gestion_interna(): void
    {
        $this->actingAs(User::factory()->create(['tenant_id' => null, 'es_super_admin' => true]));

        $this->get(route('gestion.roles.index'))->assertForbidden();
        $this->get(route('gestion.usuarios.index'))->assertForbidden();
    }

    public function test_un_rol_con_el_mismo_nombre_en_otro_tenant_no_concede_permisos(): void
    {
        $this->crearRol($this->tenant, 'Vendedor', ['roles.ver']);

        $otroTenant = Tenant::factory()->create();
        $this->crearRol($otroTenant, 'Vendedor');
        $usuarioAjeno = User::factory()->create(['tenant_id' => $otroTenant->id]);
        $this->usarTenantParaPermisos($otroTenant);
        $usuarioAjeno->assignRole('Vendedor');

        $this->actingAs($usuarioAjeno)->get(route('gestion.roles.index'))->assertForbidden();
    }

    public function test_el_frontend_recibe_los_permisos_efectivos(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['roles.ver', 'pedidos.ver']));

        $this->get(route('gestion.roles.index'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.permisos', fn ($permisos) => collect($permisos)->sort()->values()->all() === ['pedidos.ver', 'roles.ver']));
    }

    public function test_el_administrador_recibe_todo_el_catalogo(): void
    {
        $this->actingAs($this->administradorDe($this->tenant));

        $this->get(route('gestion.roles.index'))
            ->assertInertia(fn ($page) => $page->where('auth.permisos', Modulo::permisos()));
    }

    public function test_el_catalogo_queda_sembrado_en_la_base(): void
    {
        $this->assertSame(count(Modulo::permisos()), Permission::count());
    }
}
