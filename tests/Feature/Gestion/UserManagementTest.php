<?php

namespace Tests\Feature\Gestion;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararGestion();
        $this->administrador = $this->administradorDe($this->tenant);
        $this->actingAs($this->administrador);
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function datos(array $sobrescribir = []): array
    {
        return [
            'name' => 'Carlos Vendedor',
            'email' => 'carlos@ferreteria.test',
            'n_documento' => '9001',
            'cod_vendedor' => 'V01',
            'activo' => '1',
            'password' => 'Clave-Temporal-123',
            'roles' => [],
            ...$sobrescribir,
        ];
    }

    private function personal(array $atributos = []): User
    {
        return User::factory()->create(['tenant_id' => $this->tenant->id, 'es_admin' => false, ...$atributos]);
    }

    public function test_crea_personal_con_sus_roles_dentro_del_tenant(): void
    {
        $rol = $this->crearRol($this->tenant, 'Vendedor', ['pedidos.ver']);
        $this->usarTenantParaPermisos($this->tenant);

        $this->post(route('gestion.usuarios.store'), $this->datos(['roles' => [$rol->id]]))
            ->assertRedirect(route('gestion.usuarios.index'));

        $usuario = User::where('email', 'carlos@ferreteria.test')->firstOrFail();

        $this->assertSame($this->tenant->id, $usuario->tenant_id);
        $this->assertFalse($usuario->es_admin);
        $this->assertTrue($usuario->debe_cambiar_password);
        $this->assertTrue(Hash::check('Clave-Temporal-123', $usuario->password));
        $this->assertTrue($usuario->hasRole('Vendedor'));
        $this->assertTrue($usuario->can('pedidos.ver'));
    }

    public function test_no_asigna_roles_de_otro_tenant(): void
    {
        $ajeno = $this->crearRol(Tenant::factory()->create(), 'Ajeno', ['pedidos.ver']);
        $this->usarTenantParaPermisos($this->tenant);

        $this->post(route('gestion.usuarios.store'), $this->datos(['roles' => [$ajeno->id]]))
            ->assertSessionHasErrors('roles.0');

        $this->assertDatabaseMissing('users', ['email' => 'carlos@ferreteria.test']);
    }

    public function test_el_correo_es_unico_en_toda_la_plataforma_y_el_documento_dentro_del_tenant(): void
    {
        User::factory()->create(['email' => 'carlos@ferreteria.test']);
        $this->personal(['n_documento' => '9001']);

        $this->post(route('gestion.usuarios.store'), $this->datos())
            ->assertSessionHasErrors(['email', 'n_documento']);
    }

    public function test_actualiza_datos_reemplaza_roles_y_conserva_la_contrasena(): void
    {
        $vendedor = $this->crearRol($this->tenant, 'Vendedor');
        $bodeguero = $this->crearRol($this->tenant, 'Bodeguero');
        $this->usarTenantParaPermisos($this->tenant);

        $usuario = $this->personal(['password' => 'Clave-Original-1', 'debe_cambiar_password' => false]);
        $usuario->assignRole($vendedor);

        $this->put(route('gestion.usuarios.update', $usuario), $this->datos([
            'name' => 'Nombre Nuevo',
            'email' => 'nuevo@ferreteria.test',
            'password' => '',
            'roles' => [$bodeguero->id],
        ]))->assertRedirect(route('gestion.usuarios.index'));

        $usuario = User::findOrFail($usuario->id);
        $this->assertSame('Nombre Nuevo', $usuario->name);
        $this->assertTrue(Hash::check('Clave-Original-1', $usuario->password));
        $this->assertFalse($usuario->debe_cambiar_password);
        $this->assertTrue($usuario->hasRole('Bodeguero'));
        $this->assertFalse($usuario->hasRole('Vendedor'));
    }

    public function test_una_contrasena_nueva_queda_como_temporal(): void
    {
        $usuario = $this->personal(['debe_cambiar_password' => false]);

        $this->put(route('gestion.usuarios.update', $usuario), $this->datos([
            'email' => $usuario->email,
            'n_documento' => $usuario->n_documento,
            'password' => 'Otra-Clave-789',
        ]))->assertSessionHasNoErrors();

        $usuario->refresh();
        $this->assertTrue(Hash::check('Otra-Clave-789', $usuario->password));
        $this->assertTrue($usuario->debe_cambiar_password);
    }

    public function test_el_listado_excluye_administradores_y_usuarios_de_otros_tenants(): void
    {
        $this->personal(['name' => 'Propio']);
        $this->administradorDe($this->tenant);
        User::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'name' => 'Ajeno']);

        $this->get(route('gestion.usuarios.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gestion/usuarios/index')
                ->has('usuarios.data', 1)
                ->where('usuarios.data.0.name', 'Propio'));
    }

    public function test_no_gestiona_administradores_ni_usuarios_de_otro_tenant(): void
    {
        $otroAdmin = $this->administradorDe($this->tenant);
        $ajeno = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'es_admin' => false]);

        foreach ([$otroAdmin, $ajeno] as $intocable) {
            $this->put(route('gestion.usuarios.update', $intocable), $this->datos())->assertNotFound();
            $this->delete(route('gestion.usuarios.destroy', $intocable))->assertNotFound();
            $this->assertNotSoftDeleted($intocable);
        }
    }

    public function test_elimina_personal_con_borrado_logico(): void
    {
        $usuario = $this->personal();

        $this->delete(route('gestion.usuarios.destroy', $usuario))
            ->assertRedirect(route('gestion.usuarios.index'));

        $this->assertSoftDeleted($usuario);
    }

    public function test_nadie_puede_eliminar_su_propio_usuario(): void
    {
        $yo = $this->personalConPermisos($this->tenant, ['usuarios.eliminar']);
        $this->actingAs($yo);

        $this->delete(route('gestion.usuarios.destroy', $yo))
            ->assertRedirect(route('gestion.usuarios.index'));

        $this->assertNotSoftDeleted($yo);
    }
}
