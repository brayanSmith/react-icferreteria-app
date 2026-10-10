<?php

namespace Tests\Feature\Gestion;

use App\Models\Puc;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PucCrudTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
            'tipo' => '1',
            'cuenta' => '1105',
            'subcuenta' => '110505',
            'concepto' => 'Caja general',
            'descripcion' => 'Efectivo en caja',
            ...$sobrescribir,
        ];
    }

    public function test_crea_una_cuenta_y_arma_el_texto_concatenado(): void
    {
        $this->post(route('gestion.pucs.store'), $this->datos())
            ->assertRedirect(route('gestion.pucs.index'));

        $this->assertDatabaseHas('pucs', [
            'tenant_id' => $this->tenant->id,
            'subcuenta' => '110505',
            'concatenar_subcuenta_concepto' => '110505 - Caja general',
        ]);
    }

    public function test_la_subcuenta_es_unica_en_el_tenant_pero_repetible_en_otro(): void
    {
        $this->crearEn(Puc::class, $this->tenant, $this->datos());

        $this->post(route('gestion.pucs.store'), $this->datos())->assertSessionHasErrors('subcuenta');

        $this->actingAs($this->administradorDe(Tenant::factory()->create()));
        $this->post(route('gestion.pucs.store'), $this->datos())->assertSessionHasNoErrors();
    }

    public function test_valida_el_tipo_de_cuenta(): void
    {
        $this->post(route('gestion.pucs.store'), $this->datos(['tipo' => '10']))->assertSessionHasErrors('tipo');
        $this->post(route('gestion.pucs.store'), $this->datos(['concepto' => '']))->assertSessionHasErrors('concepto');
    }

    public function test_actualiza_conservando_su_propia_subcuenta_y_recalcula_el_concatenado(): void
    {
        $puc = $this->crearEn(Puc::class, $this->tenant, $this->datos());

        $this->put(route('gestion.pucs.update', $puc), $this->datos(['concepto' => 'Caja menor']))
            ->assertSessionHasNoErrors();

        $this->assertSame('110505 - Caja menor', $puc->refresh()->concatenar_subcuenta_concepto);
    }

    public function test_el_listado_trae_todas_las_cuentas_del_tenant_para_filtrar_en_el_navegador(): void
    {
        $this->crearEn(Puc::class, $this->tenant, $this->datos());
        $this->crearEn(Puc::class, $this->tenant, $this->datos(['subcuenta' => '220505', 'cuenta' => '2205', 'concepto' => 'Proveedores']));
        $this->crearEn(Puc::class, Tenant::factory()->create(), $this->datos(['subcuenta' => '110599', 'concepto' => 'Caja ajena']));

        $this->get(route('gestion.pucs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gestion/pucs/index')
                ->has('cuentas.data', 2)
                ->where('cuentas.data.0.concepto', 'Caja general')
                ->where('cuentas.data.1.concepto', 'Proveedores'));
    }

    public function test_elimina_una_cuenta(): void
    {
        $puc = $this->crearEn(Puc::class, $this->tenant, $this->datos());

        $this->delete(route('gestion.pucs.destroy', $puc))->assertRedirect(route('gestion.pucs.index'));

        $this->assertModelMissing($puc);
    }

    public function test_no_toca_cuentas_de_otro_tenant(): void
    {
        $ajena = $this->crearEn(Puc::class, Tenant::factory()->create(), $this->datos());

        $this->put(route('gestion.pucs.update', $ajena), $this->datos())->assertNotFound();
        $this->delete(route('gestion.pucs.destroy', $ajena))->assertNotFound();
    }

    public function test_exige_permiso_por_accion(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['pucs.ver']));

        $this->get(route('gestion.pucs.index'))->assertOk();
        $this->post(route('gestion.pucs.store'), $this->datos())->assertForbidden();
    }
}
