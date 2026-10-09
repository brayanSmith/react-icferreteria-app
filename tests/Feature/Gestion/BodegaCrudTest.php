<?php

namespace Tests\Feature\Gestion;

use App\Models\Bodega;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BodegaCrudTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararGestion();
        $this->actingAs($this->administradorDe($this->tenant));
    }

    public function test_crea_una_bodega(): void
    {
        $this->post(route('gestion.bodegas.store'), ['nombre_bodega' => 'Central', 'ubicacion_bodega' => 'Calle 1'])
            ->assertRedirect(route('gestion.bodegas.index'));

        $this->assertDatabaseHas('bodegas', ['tenant_id' => $this->tenant->id, 'nombre_bodega' => 'Central']);
    }

    public function test_el_nombre_es_unico_en_el_tenant_pero_repetible_en_otro(): void
    {
        $this->crearEn(Bodega::class, $this->tenant, ['nombre_bodega' => 'Central']);

        $this->post(route('gestion.bodegas.store'), ['nombre_bodega' => 'Central'])->assertSessionHasErrors('nombre_bodega');

        $this->actingAs($this->administradorDe(Tenant::factory()->create()));
        $this->post(route('gestion.bodegas.store'), ['nombre_bodega' => 'Central'])->assertSessionHasNoErrors();
    }

    public function test_el_nombre_de_una_bodega_eliminada_sigue_reservado_y_avisa_que_se_restaure(): void
    {
        $this->crearEn(Bodega::class, $this->tenant, ['nombre_bodega' => 'Vieja'])->delete();

        $this->post(route('gestion.bodegas.store'), ['nombre_bodega' => 'Vieja'])->assertSessionHasErrors('nombre_bodega');
        $this->assertStringContainsString('restáuralo', session('errors')->first('nombre_bodega'));
    }

    public function test_actualiza_una_bodega_conservando_su_nombre(): void
    {
        $bodega = $this->crearEn(Bodega::class, $this->tenant, ['nombre_bodega' => 'Central']);

        $this->put(route('gestion.bodegas.update', $bodega), ['nombre_bodega' => 'Central', 'ubicacion_bodega' => 'Nueva'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nueva', $bodega->refresh()->ubicacion_bodega);
    }

    public function test_el_listado_muestra_activas_y_eliminadas_por_separado(): void
    {
        $this->crearEn(Bodega::class, $this->tenant, ['nombre_bodega' => 'Activa']);
        $this->crearEn(Bodega::class, $this->tenant, ['nombre_bodega' => 'Borrada'])->delete();
        $this->crearEn(Bodega::class, Tenant::factory()->create(), ['nombre_bodega' => 'Ajena']);

        $this->get(route('gestion.bodegas.index'))
            ->assertInertia(fn ($page) => $page
                ->component('gestion/bodegas/index')
                ->has('bodegas.data', 1)
                ->where('bodegas.data.0.nombre_bodega', 'Activa'));

        $this->get(route('gestion.bodegas.index', ['eliminadas' => 1]))
            ->assertInertia(fn ($page) => $page
                ->has('bodegas.data', 1)
                ->where('bodegas.data.0.nombre_bodega', 'Borrada'));
    }

    public function test_elimina_de_forma_logica_y_restaura(): void
    {
        $bodega = $this->crearEn(Bodega::class, $this->tenant, ['nombre_bodega' => 'Central']);

        $this->delete(route('gestion.bodegas.destroy', $bodega))->assertRedirect(route('gestion.bodegas.index'));
        $this->assertSoftDeleted($bodega);

        $this->post(route('gestion.bodegas.restore', $bodega))->assertRedirect(route('gestion.bodegas.index'));
        $this->assertNotSoftDeleted($bodega);
    }

    public function test_no_elimina_una_bodega_con_productos(): void
    {
        $catalogos = $this->catalogosBasicosDe($this->tenant);
        $this->crearProductoEn($this->tenant, $catalogos);

        $this->delete(route('gestion.bodegas.destroy', $catalogos['bodega']))->assertRedirect();

        $this->assertNotSoftDeleted($catalogos['bodega']);
    }

    public function test_no_toca_bodegas_de_otro_tenant(): void
    {
        $ajena = $this->crearEn(Bodega::class, Tenant::factory()->create(), ['nombre_bodega' => 'Ajena']);

        $this->put(route('gestion.bodegas.update', $ajena), ['nombre_bodega' => 'X'])->assertNotFound();
        $this->delete(route('gestion.bodegas.destroy', $ajena))->assertNotFound();
    }

    public function test_exige_permiso_por_accion(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['bodegas.ver']));

        $this->get(route('gestion.bodegas.index'))->assertOk();
        $this->post(route('gestion.bodegas.store'), ['nombre_bodega' => 'X'])->assertForbidden();
    }
}
