<?php

namespace Tests\Feature\Gestion;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Ruta;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClienteCrudTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->prepararGestion();
        $this->actingAs($this->administradorDe($this->tenant));
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function datos(array $sobrescribir = []): array
    {
        return [
            'tipo_documento' => 'NIT',
            'numero_documento' => '900123456',
            'razon_social' => 'Ferretería El Tornillo SAS',
            'email' => 'compras@tornillo.test',
            'telefono' => '3001234567',
            'ciudad' => 'Pasto',
            'tipo_cliente' => 'ELECTRONICO',
            'retenedor_fuente' => 'NO',
            'limite_credito' => '500000',
            'activo' => '1',
            ...$sobrescribir,
        ];
    }

    private function crearCliente(array $sobrescribir = [], ?Tenant $tenant = null): Cliente
    {
        return $this->crearEn(Cliente::class, $tenant ?? $this->tenant, [
            'tipo_documento' => 'NIT',
            'numero_documento' => '900123456',
            'razon_social' => 'Cliente base',
            ...$sobrescribir,
        ]);
    }

    public function test_crea_un_cliente_con_numero_consecutivo(): void
    {
        $this->post(route('gestion.clientes.store'), $this->datos())
            ->assertRedirect(route('gestion.clientes.index'));
        $this->post(route('gestion.clientes.store'), $this->datos(['numero_documento' => '2', 'email' => 'otro@tornillo.test']));

        $this->assertDatabaseHas('clientes', [
            'tenant_id' => $this->tenant->id,
            'numero_documento' => '900123456',
            'numero' => 1,
        ]);
        $this->assertDatabaseHas('clientes', ['numero_documento' => '2', 'numero' => 2]);
    }

    public function test_el_correo_vacio_se_guarda_como_nulo_y_no_choca_con_otros(): void
    {
        $this->post(route('gestion.clientes.store'), $this->datos(['email' => '']))->assertSessionHasNoErrors();
        $this->post(route('gestion.clientes.store'), $this->datos(['numero_documento' => '2', 'email' => '']))->assertSessionHasNoErrors();

        $this->assertSame(2, Cliente::whereNull('email')->count());
    }

    public function test_valida_los_valores_permitidos_y_el_correo(): void
    {
        $this->post(route('gestion.clientes.store'), $this->datos([
            'tipo_documento' => 'LICENCIA',
            'tipo_cliente' => 'MAYORISTA',
            'retenedor_fuente' => 'QUIZAS',
            'email' => 'no-es-correo',
            'razon_social' => '',
            'limite_credito' => '-1',
        ]))->assertSessionHasErrors(['tipo_documento', 'tipo_cliente', 'retenedor_fuente', 'email', 'razon_social', 'limite_credito']);
    }

    public function test_el_documento_y_el_correo_son_unicos_en_el_tenant_pero_repetibles_en_otro(): void
    {
        $this->crearCliente(['email' => 'compras@tornillo.test']);

        $this->post(route('gestion.clientes.store'), $this->datos())
            ->assertSessionHasErrors(['numero_documento', 'email']);

        $this->actingAs($this->administradorDe(Tenant::factory()->create()));
        $this->post(route('gestion.clientes.store'), $this->datos())->assertSessionHasNoErrors();
    }

    public function test_no_enlaza_con_rutas_ni_comerciales_de_otro_tenant(): void
    {
        $otroTenant = Tenant::factory()->create();
        $rutaAjena = $this->crearEn(Ruta::class, $otroTenant, ['ruta' => 'Ajena']);
        $comercialAjeno = User::factory()->create(['tenant_id' => $otroTenant->id]);

        $this->post(route('gestion.clientes.store'), $this->datos([
            'ruta_id' => $rutaAjena->id,
            'comercial_id' => $comercialAjeno->id,
        ]))->assertSessionHasErrors(['ruta_id', 'comercial_id']);

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_asigna_ruta_y_comercial_del_propio_tenant(): void
    {
        $ruta = $this->crearEn(Ruta::class, $this->tenant, ['ruta' => 'Norte']);
        $comercial = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->post(route('gestion.clientes.store'), $this->datos([
            'ruta_id' => $ruta->id,
            'comercial_id' => $comercial->id,
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clientes', ['ruta_id' => $ruta->id, 'comercial_id' => $comercial->id]);
    }

    public function test_actualiza_un_cliente_conservando_su_documento_y_correo(): void
    {
        $cliente = $this->crearCliente(['email' => 'compras@tornillo.test']);

        $this->put(route('gestion.clientes.update', $cliente), $this->datos(['razon_social' => 'Nuevo nombre']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Nuevo nombre', $cliente->refresh()->razon_social);
    }

    public function test_guarda_reemplaza_y_quita_el_rut(): void
    {
        $this->post(route('gestion.clientes.store'), $this->datos(['rut' => UploadedFile::fake()->create('rut.pdf', 100, 'application/pdf')]));
        $cliente = Cliente::firstOrFail();
        $primero = $cliente->rut_imagen;
        Storage::disk('public')->assertExists($primero);
        $this->assertStringStartsWith("clientes/{$this->tenant->id}/", $primero);

        $this->put(route('gestion.clientes.update', $cliente), $this->datos(['rut' => UploadedFile::fake()->image('rut.png')]));
        $segundo = $cliente->refresh()->rut_imagen;
        Storage::disk('public')->assertMissing($primero);
        Storage::disk('public')->assertExists($segundo);

        $this->put(route('gestion.clientes.update', $cliente), $this->datos(['quitar_rut' => '1']));
        $this->assertNull($cliente->refresh()->rut_imagen);
        Storage::disk('public')->assertMissing($segundo);
    }

    public function test_rechaza_un_rut_con_formato_no_permitido(): void
    {
        $this->post(route('gestion.clientes.store'), $this->datos(['rut' => UploadedFile::fake()->create('rut.exe', 10)]))
            ->assertSessionHasErrors('rut');
    }

    public function test_el_listado_busca_y_separa_activos_de_eliminados(): void
    {
        $this->crearCliente(['razon_social' => 'Tornillos del Sur', 'numero_documento' => '1']);
        $this->crearCliente(['razon_social' => 'Pinturas Pasto', 'numero_documento' => '2']);
        $this->crearCliente(['razon_social' => 'Cerrado SA', 'numero_documento' => '3'])->delete();
        $this->crearCliente(['razon_social' => 'Tornillos ajenos', 'numero_documento' => '4'], Tenant::factory()->create());

        $this->get(route('gestion.clientes.index', ['buscar' => 'Tornillos']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gestion/clientes/index')
                ->has('clientes.data', 1)
                ->where('clientes.data.0.razon_social', 'Tornillos del Sur'));

        $this->get(route('gestion.clientes.index', ['eliminados' => 1]))
            ->assertInertia(fn ($page) => $page
                ->has('clientes.data', 1)
                ->where('clientes.data.0.razon_social', 'Cerrado SA'));
    }

    public function test_el_listado_trae_las_rutas_y_los_comerciales_solo_del_tenant(): void
    {
        $this->crearEn(Ruta::class, $this->tenant, ['ruta' => 'Norte']);
        $this->crearEn(Ruta::class, Tenant::factory()->create(), ['ruta' => 'Ajena']);
        User::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'name' => 'Ajeno']);

        $this->get(route('gestion.clientes.index'))
            ->assertInertia(fn ($page) => $page
                ->has('rutas', 1)
                ->where('rutas.0.nombre', 'Norte')
                ->has('comerciales', 1));
    }

    public function test_elimina_de_forma_logica_y_restaura(): void
    {
        $cliente = $this->crearCliente();

        $this->delete(route('gestion.clientes.destroy', $cliente))->assertRedirect(route('gestion.clientes.index'));
        $this->assertSoftDeleted($cliente);

        $this->post(route('gestion.clientes.store'), $this->datos())->assertSessionHasErrors('numero_documento');
        $this->assertStringContainsString('restáuralo', session('errors')->first('numero_documento'));

        $this->post(route('gestion.clientes.restore', $cliente))->assertRedirect(route('gestion.clientes.index'));
        $this->assertNotSoftDeleted($cliente);
    }

    public function test_no_elimina_a_un_cliente_con_pedidos_pendientes_de_pago(): void
    {
        $cliente = $this->crearCliente();
        $catalogos = $this->catalogosBasicosDe($this->tenant);
        $pedido = new Pedido(['cliente_id' => $cliente->id, 'bodega_id' => $catalogos['bodega']->id, 'estado' => 'FACTURADO', 'estado_pago' => 'EN_CARTERA']);
        $pedido->forceFill(['tenant_id' => $this->tenant->id, 'numero' => 1, 'user_id' => auth()->id(), 'alistador_id' => auth()->id()])->saveQuietly();

        $this->delete(route('gestion.clientes.destroy', $cliente))->assertRedirect();

        $this->assertNotSoftDeleted($cliente);
    }

    public function test_no_toca_clientes_de_otro_tenant(): void
    {
        $ajeno = $this->crearCliente([], Tenant::factory()->create());

        $this->put(route('gestion.clientes.update', $ajeno), $this->datos())->assertNotFound();
        $this->delete(route('gestion.clientes.destroy', $ajeno))->assertNotFound();
        $this->post(route('gestion.clientes.restore', $ajeno))->assertNotFound();
    }

    public function test_exige_permiso_por_accion(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['clientes.ver']));

        $this->get(route('gestion.clientes.index'))->assertOk();
        $this->post(route('gestion.clientes.store'), $this->datos())->assertForbidden();
    }
}
