<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantAdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->actingAs(User::factory()->create(['tenant_id' => null, 'es_super_admin' => true]));
    }

    private function crearAdmin(array $atributos = [], ?Tenant $tenant = null): User
    {
        return User::factory()->create([
            'tenant_id' => ($tenant ?? $this->tenant)->id,
            'es_admin' => true,
            ...$atributos,
        ]);
    }

    /**
     * @param  array<string, string>  $sobrescribir
     * @return array<string, string>
     */
    private function datos(array $sobrescribir = []): array
    {
        return [
            'name' => 'Nombre Nuevo',
            'email' => 'nuevo@ferreteria.test',
            'n_documento' => '555',
            ...$sobrescribir,
        ];
    }

    public function test_actualiza_los_datos_sin_cambiar_la_contrasena_si_no_se_envia(): void
    {
        $admin = $this->crearAdmin(['password' => 'Clave-Original-1', 'debe_cambiar_password' => false]);

        $this->put(route('admin.tenants.admins.update', [$this->tenant, $admin]), $this->datos())
            ->assertRedirect(route('admin.tenants.edit', $this->tenant));

        $admin->refresh();
        $this->assertSame('Nombre Nuevo', $admin->name);
        $this->assertSame('nuevo@ferreteria.test', $admin->email);
        $this->assertTrue(Hash::check('Clave-Original-1', $admin->password));
        $this->assertFalse($admin->debe_cambiar_password);
    }

    public function test_una_contrasena_nueva_reemplaza_la_actual_y_queda_como_temporal(): void
    {
        $admin = $this->crearAdmin(['debe_cambiar_password' => false]);

        $this->put(
            route('admin.tenants.admins.update', [$this->tenant, $admin]),
            $this->datos(['password' => 'Otra-Clave-789']),
        )->assertSessionHasNoErrors();

        $admin->refresh();
        $this->assertTrue(Hash::check('Otra-Clave-789', $admin->password));
        $this->assertTrue($admin->debe_cambiar_password);
    }

    public function test_puede_conservar_su_propio_correo_y_documento(): void
    {
        $admin = $this->crearAdmin(['email' => 'yo@ferreteria.test', 'n_documento' => '111']);

        $this->put(
            route('admin.tenants.admins.update', [$this->tenant, $admin]),
            $this->datos(['email' => 'yo@ferreteria.test', 'n_documento' => '111']),
        )->assertSessionHasNoErrors();
    }

    public function test_rechaza_un_correo_o_documento_de_otro_usuario(): void
    {
        $admin = $this->crearAdmin();
        User::factory()->create(['email' => 'ocupado@ferreteria.test']);
        $this->crearAdmin(['n_documento' => '777']);

        $this->put(
            route('admin.tenants.admins.update', [$this->tenant, $admin]),
            $this->datos(['email' => 'ocupado@ferreteria.test', 'n_documento' => '777']),
        )->assertSessionHasErrors(['email', 'n_documento']);
    }

    public function test_no_edita_administradores_de_otro_tenant(): void
    {
        $otroTenant = Tenant::factory()->create();
        $ajeno = $this->crearAdmin(tenant: $otroTenant);

        $this->put(route('admin.tenants.admins.update', [$this->tenant, $ajeno]), $this->datos())
            ->assertNotFound();
    }

    public function test_no_trata_como_administrador_a_un_usuario_comun(): void
    {
        $empleado = $this->crearAdmin(['es_admin' => false]);

        $this->put(route('admin.tenants.admins.update', [$this->tenant, $empleado]), $this->datos())
            ->assertNotFound();
    }

    public function test_elimina_a_un_administrador_con_borrado_logico(): void
    {
        $this->crearAdmin();
        $sobrante = $this->crearAdmin();

        $this->delete(route('admin.tenants.admins.destroy', [$this->tenant, $sobrante]))
            ->assertRedirect(route('admin.tenants.edit', $this->tenant));

        $this->assertSoftDeleted($sobrante);
    }

    public function test_no_elimina_al_unico_administrador(): void
    {
        $unico = $this->crearAdmin();

        $this->delete(route('admin.tenants.admins.destroy', [$this->tenant, $unico]))
            ->assertRedirect(route('admin.tenants.edit', $this->tenant));

        $this->assertNotSoftDeleted($unico);
    }

    public function test_solo_el_super_admin_edita_o_elimina_administradores(): void
    {
        $admin = $this->crearAdmin();
        $this->crearAdmin();

        $this->actingAs($admin);

        $this->put(route('admin.tenants.admins.update', [$this->tenant, $admin]), $this->datos())->assertForbidden();
        $this->delete(route('admin.tenants.admins.destroy', [$this->tenant, $admin]))->assertForbidden();
    }

    public function test_la_pantalla_de_edicion_no_lista_a_los_administradores_eliminados(): void
    {
        $this->crearAdmin(['name' => 'Activo']);
        $this->crearAdmin(['name' => 'Borrado'])->delete();

        $this->get(route('admin.tenants.edit', $this->tenant))
            ->assertInertia(fn ($page) => $page
                ->has('administradores', 1)
                ->where('administradores.0.name', 'Activo'));
    }
}
