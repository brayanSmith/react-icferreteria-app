<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantAdminCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, string>
     */
    private function datosAdmin(array $sobrescribir = []): array
    {
        return [
            'name' => 'Ana Admin',
            'email' => 'ana@ferreteria.test',
            'n_documento' => '123456',
            'password' => 'Clave-Temporal-123',
            ...$sobrescribir,
        ];
    }

    /**
     * @param  list<array<string, string>>  $admins
     * @return array<string, mixed>
     */
    private function datosAlta(array $admins): array
    {
        return ['nombre' => 'Ferreteria Nueva', 'activo' => '1', 'admins' => $admins];
    }

    private function actuarComoSuperAdmin(): void
    {
        $this->actingAs(User::factory()->create(['tenant_id' => null, 'es_super_admin' => true]));
    }

    public function test_al_crear_el_tenant_se_crea_su_administrador(): void
    {
        $this->actuarComoSuperAdmin();

        $this->post(route('admin.tenants.store'), $this->datosAlta([$this->datosAdmin()]))
            ->assertRedirect(route('admin.tenants.index'));

        $tenant = Tenant::where('slug', 'ferreteria-nueva')->firstOrFail();
        $administrador = User::where('email', 'ana@ferreteria.test')->firstOrFail();

        $this->assertSame($tenant->id, $administrador->tenant_id);
        $this->assertTrue($administrador->es_admin);
        $this->assertFalse($administrador->es_super_admin);
        $this->assertTrue($administrador->debe_cambiar_password);
        $this->assertNotNull($administrador->email_verified_at);
        $this->assertTrue(Hash::check('Clave-Temporal-123', $administrador->password));
    }

    public function test_puede_crear_varios_administradores_al_crear_el_tenant(): void
    {
        $this->actuarComoSuperAdmin();

        $this->post(route('admin.tenants.store'), $this->datosAlta([
            $this->datosAdmin(),
            $this->datosAdmin(['name' => 'Beto Admin', 'email' => 'beto@ferreteria.test', 'n_documento' => '654321']),
        ]))->assertRedirect(route('admin.tenants.index'));

        $tenant = Tenant::where('slug', 'ferreteria-nueva')->firstOrFail();

        $this->assertSame(2, User::where('tenant_id', $tenant->id)->where('es_admin', true)->count());
    }

    public function test_rechaza_administradores_repetidos_dentro_de_la_misma_solicitud(): void
    {
        $this->actuarComoSuperAdmin();

        $this->post(route('admin.tenants.store'), $this->datosAlta([
            $this->datosAdmin(),
            $this->datosAdmin(['name' => 'Otro', 'n_documento' => '999']),
        ]))->assertSessionHasErrors(['admins.0.email', 'admins.1.email']);

        $this->assertDatabaseMissing('tenants', ['slug' => 'ferreteria-nueva']);
    }

    public function test_si_un_administrador_es_invalido_no_se_crea_el_tenant(): void
    {
        $this->actuarComoSuperAdmin();
        User::factory()->create(['email' => 'beto@ferreteria.test']);

        $this->post(route('admin.tenants.store'), $this->datosAlta([
            $this->datosAdmin(),
            $this->datosAdmin(['email' => 'beto@ferreteria.test', 'n_documento' => '654321']),
        ]))->assertSessionHasErrors('admins.1.email');

        $this->assertDatabaseMissing('tenants', ['slug' => 'ferreteria-nueva']);
        $this->assertDatabaseMissing('users', ['email' => 'ana@ferreteria.test']);
    }

    public function test_exige_al_menos_un_administrador(): void
    {
        $this->actuarComoSuperAdmin();

        $this->post(route('admin.tenants.store'), ['nombre' => 'Sin Admin'])
            ->assertSessionHasErrors('admins');

        $this->assertDatabaseMissing('tenants', ['slug' => 'sin-admin']);
    }

    public function test_agrega_un_administrador_a_un_tenant_existente(): void
    {
        $this->actuarComoSuperAdmin();
        $tenant = Tenant::factory()->create();

        $this->post(route('admin.tenants.admins.store', $tenant), $this->datosAdmin())
            ->assertRedirect(route('admin.tenants.edit', $tenant));

        $this->assertDatabaseHas('users', [
            'email' => 'ana@ferreteria.test',
            'tenant_id' => $tenant->id,
            'es_admin' => true,
            'debe_cambiar_password' => true,
        ]);
    }

    public function test_el_documento_no_puede_repetirse_dentro_del_tenant_pero_si_en_otro(): void
    {
        $this->actuarComoSuperAdmin();
        $tenant = Tenant::factory()->create();
        $otroTenant = Tenant::factory()->create();
        User::factory()->create(['tenant_id' => $tenant->id, 'n_documento' => '123456']);

        $this->post(route('admin.tenants.admins.store', $tenant), $this->datosAdmin())
            ->assertSessionHasErrors('n_documento');

        $this->post(route('admin.tenants.admins.store', $otroTenant), $this->datosAdmin())
            ->assertSessionHasNoErrors();
    }

    public function test_la_pantalla_de_edicion_lista_los_administradores_del_tenant(): void
    {
        $this->actuarComoSuperAdmin();
        $tenant = Tenant::factory()->create();
        User::factory()->create(['tenant_id' => $tenant->id, 'es_admin' => true, 'name' => 'Ana']);
        User::factory()->create(['tenant_id' => $tenant->id, 'es_admin' => false, 'name' => 'Empleado']);

        $this->get(route('admin.tenants.edit', $tenant))
            ->assertInertia(fn ($page) => $page
                ->has('administradores', 1)
                ->where('administradores.0.name', 'Ana'));
    }

    public function test_solo_el_super_admin_agrega_administradores(): void
    {
        $tenant = Tenant::factory()->create();
        $empleado = User::factory()->create(['tenant_id' => $tenant->id, 'es_admin' => true]);

        $this->actingAs($empleado)
            ->post(route('admin.tenants.admins.store', $tenant), $this->datosAdmin())
            ->assertForbidden();
    }

    public function test_el_administrador_de_un_tenant_no_accede_al_panel_de_tenants(): void
    {
        $this->actuarComoSuperAdmin();
        $this->post(route('admin.tenants.store'), $this->datosAlta([$this->datosAdmin()]));

        $administrador = User::where('email', 'ana@ferreteria.test')->firstOrFail();
        $administrador->forceFill(['debe_cambiar_password' => false])->save();

        $this->actingAs($administrador)->get(route('admin.tenants.index'))->assertForbidden();
    }

    public function test_con_contrasena_temporal_se_redirige_a_cambiarla(): void
    {
        $usuario = User::factory()->create(['debe_cambiar_password' => true]);

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertRedirect(route('security.edit'));
    }

    public function test_con_contrasena_temporal_se_puede_llegar_a_cambiar_la_contrasena(): void
    {
        $usuario = User::factory()->create(['debe_cambiar_password' => true]);

        $this->actingAs($usuario)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk();
    }

    public function test_cambiar_la_contrasena_levanta_la_obligacion(): void
    {
        $usuario = User::factory()->create(['debe_cambiar_password' => true]);

        $this->actingAs($usuario)
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'Nueva-Clave-456',
                'password_confirmation' => 'Nueva-Clave-456',
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($usuario->refresh()->debe_cambiar_password);
        $this->actingAs($usuario)->get(route('dashboard'))->assertOk();
    }
}
