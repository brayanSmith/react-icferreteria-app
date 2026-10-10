<?php

namespace Tests\Feature\Gestion;

use App\Models\Proveedor;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProveedorCrudTest extends TestCase
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
            'nombre_proveedor' => 'Aceros del Sur',
            'razon_social_proveedor' => 'Aceros del Sur SAS',
            'nit_proveedor' => '900111222-3',
            'tipo_proveedor' => 'ELECTRONICO',
            'categoria_proveedor' => 'DECLARANTE',
            'ciudad_proveedor' => 'Pasto',
            'telefono_proveedor' => '3001112233',
            ...$sobrescribir,
        ];
    }

    public function test_crea_un_proveedor(): void
    {
        $this->post(route('gestion.proveedores.store'), $this->datos())
            ->assertRedirect(route('gestion.proveedores.index'));

        $this->assertDatabaseHas('proveedors', ['tenant_id' => $this->tenant->id, 'nit_proveedor' => '900111222-3']);
    }

    public function test_el_nit_es_unico_en_el_tenant_pero_repetible_en_otro(): void
    {
        $this->crearEn(Proveedor::class, $this->tenant, $this->datos());

        $this->post(route('gestion.proveedores.store'), $this->datos())->assertSessionHasErrors('nit_proveedor');

        $this->actingAs($this->administradorDe(Tenant::factory()->create()));
        $this->post(route('gestion.proveedores.store'), $this->datos())->assertSessionHasNoErrors();
    }

    public function test_valida_los_valores_permitidos(): void
    {
        $this->post(route('gestion.proveedores.store'), $this->datos([
            'tipo_proveedor' => 'OTRO',
            'categoria_proveedor' => 'NADA',
            'tipo_cuenta_proveedor' => 'PLAZO_FIJO',
            'nombre_proveedor' => '',
        ]))->assertSessionHasErrors(['tipo_proveedor', 'categoria_proveedor', 'tipo_cuenta_proveedor', 'nombre_proveedor']);
    }

    public function test_el_valor_del_flete_solo_aplica_si_cobra_flete(): void
    {
        $this->post(route('gestion.proveedores.store'), $this->datos(['nit_proveedor' => '1', 'flete' => '0', 'valor_flete' => '5000']));
        $this->post(route('gestion.proveedores.store'), $this->datos(['nit_proveedor' => '2', 'flete' => '1', 'valor_flete' => '5000']));

        $this->assertDatabaseHas('proveedors', ['nit_proveedor' => '1', 'flete' => false, 'valor_flete' => 0]);
        $this->assertDatabaseHas('proveedors', ['nit_proveedor' => '2', 'flete' => true, 'valor_flete' => 5000]);
    }

    public function test_guarda_reemplaza_y_quita_el_rut_adjunto(): void
    {
        $this->post(route('gestion.proveedores.store'), $this->datos(['rut' => UploadedFile::fake()->create('rut.pdf', 100, 'application/pdf')]));
        $proveedor = Proveedor::firstOrFail();
        $primero = $proveedor->rut_proveedor_imagen;
        Storage::disk('public')->assertExists($primero);
        $this->assertStringStartsWith("proveedores/{$this->tenant->id}/", $primero);

        $this->put(route('gestion.proveedores.update', $proveedor), $this->datos(['rut' => UploadedFile::fake()->image('rut.png')]));
        $segundo = $proveedor->refresh()->rut_proveedor_imagen;
        Storage::disk('public')->assertMissing($primero);
        Storage::disk('public')->assertExists($segundo);

        $this->put(route('gestion.proveedores.update', $proveedor), $this->datos(['quitar_rut' => '1']));
        $this->assertNull($proveedor->refresh()->rut_proveedor_imagen);
        Storage::disk('public')->assertMissing($segundo);
    }

    public function test_conserva_el_rut_si_no_se_envia_uno_nuevo(): void
    {
        $this->post(route('gestion.proveedores.store'), $this->datos(['rut' => UploadedFile::fake()->image('rut.png')]));
        $proveedor = Proveedor::firstOrFail();
        $ruta = $proveedor->rut_proveedor_imagen;

        $this->put(route('gestion.proveedores.update', $proveedor), $this->datos(['nombre_proveedor' => 'Otro nombre']));

        $this->assertSame($ruta, $proveedor->refresh()->rut_proveedor_imagen);
    }

    public function test_rechaza_un_rut_con_formato_no_permitido(): void
    {
        $this->post(route('gestion.proveedores.store'), $this->datos(['rut' => UploadedFile::fake()->create('rut.exe', 10)]))
            ->assertSessionHasErrors('rut');
    }

    public function test_el_listado_trae_activos_y_eliminados_del_tenant_para_filtrar_en_el_navegador(): void
    {
        $this->crearEn(Proveedor::class, $this->tenant, $this->datos());
        // Se anula la razón social para que solo el primero contenga la palabra buscada.
        $this->crearEn(Proveedor::class, $this->tenant, $this->datos(['nombre_proveedor' => 'Tornillos SA', 'razon_social_proveedor' => null, 'nit_proveedor' => '2']));
        $this->crearEn(Proveedor::class, $this->tenant, $this->datos(['nombre_proveedor' => 'Cerrado SA', 'razon_social_proveedor' => null, 'nit_proveedor' => '3']))->delete();
        $this->crearEn(Proveedor::class, Tenant::factory()->create(), $this->datos(['nombre_proveedor' => 'Aceros ajenos', 'nit_proveedor' => '4']));

        $this->get(route('gestion.proveedores.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gestion/proveedores/index')
                ->has('proveedores.data', 3)
                ->where('proveedores.data.0.nombre_proveedor', 'Aceros del Sur')
                ->where('proveedores.data.0.deleted_at', null)
                ->where('proveedores.data.1.nombre_proveedor', 'Cerrado SA')
                ->whereNot('proveedores.data.1.deleted_at', null)
                ->where('proveedores.data.2.nombre_proveedor', 'Tornillos SA'));
    }

    public function test_elimina_de_forma_logica_y_restaura(): void
    {
        $proveedor = $this->crearEn(Proveedor::class, $this->tenant, $this->datos());

        $this->delete(route('gestion.proveedores.destroy', $proveedor))->assertRedirect(route('gestion.proveedores.index'));
        $this->assertSoftDeleted($proveedor);

        $this->post(route('gestion.proveedores.store'), $this->datos())->assertSessionHasErrors('nit_proveedor');
        $this->assertStringContainsString('restáuralo', session('errors')->first('nit_proveedor'));

        $this->post(route('gestion.proveedores.restore', $proveedor))->assertRedirect(route('gestion.proveedores.index'));
        $this->assertNotSoftDeleted($proveedor);
    }

    public function test_no_toca_proveedores_de_otro_tenant(): void
    {
        $ajeno = $this->crearEn(Proveedor::class, Tenant::factory()->create(), $this->datos());

        $this->put(route('gestion.proveedores.update', $ajeno), $this->datos())->assertNotFound();
        $this->delete(route('gestion.proveedores.destroy', $ajeno))->assertNotFound();
        $this->post(route('gestion.proveedores.restore', $ajeno))->assertNotFound();
    }

    public function test_exige_permiso_por_accion(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['proveedores.ver']));

        $this->get(route('gestion.proveedores.index'))->assertOk();
        $this->post(route('gestion.proveedores.store'), $this->datos())->assertForbidden();
    }
}
