<?php

namespace Tests\Feature\Gestion;

use App\Models\Cliente;
use App\Models\Ruta;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutaCrudTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararGestion();
        $this->actingAs($this->administradorDe($this->tenant));
    }

    public function test_crea_una_ruta(): void
    {
        $this->post(route('gestion.rutas.store'), ['ruta' => 'Norte', 'descripcion' => 'Zona norte'])
            ->assertRedirect(route('gestion.rutas.index'));

        $this->assertDatabaseHas('rutas', ['tenant_id' => $this->tenant->id, 'ruta' => 'Norte']);
    }

    public function test_el_nombre_es_unico_en_el_tenant_pero_repetible_en_otro(): void
    {
        $this->crearEn(Ruta::class, $this->tenant, ['ruta' => 'Norte']);

        $this->post(route('gestion.rutas.store'), ['ruta' => 'Norte'])->assertSessionHasErrors('ruta');

        $this->actingAs($this->administradorDe(Tenant::factory()->create()));
        $this->post(route('gestion.rutas.store'), ['ruta' => 'Norte'])->assertSessionHasNoErrors();
    }

    public function test_actualiza_una_ruta_conservando_su_nombre(): void
    {
        $ruta = $this->crearEn(Ruta::class, $this->tenant, ['ruta' => 'Norte']);

        $this->put(route('gestion.rutas.update', $ruta), ['ruta' => 'Norte', 'descripcion' => 'Nueva'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nueva', $ruta->refresh()->descripcion);
    }

    public function test_el_listado_cuenta_los_clientes_de_cada_ruta(): void
    {
        $ruta = $this->crearEn(Ruta::class, $this->tenant, ['ruta' => 'Norte']);
        $this->crearEn(Cliente::class, $this->tenant, [
            'tipo_documento' => 'CC',
            'numero_documento' => '1',
            'razon_social' => 'Cliente 1',
            'ruta_id' => $ruta->id,
        ]);

        $this->get(route('gestion.rutas.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gestion/rutas/index')
                ->where('rutas.data.0.clientes_count', 1));
    }

    public function test_no_elimina_una_ruta_con_clientes_ni_siquiera_eliminados(): void
    {
        $ruta = $this->crearEn(Ruta::class, $this->tenant, ['ruta' => 'Norte']);
        $cliente = $this->crearEn(Cliente::class, $this->tenant, [
            'tipo_documento' => 'CC',
            'numero_documento' => '1',
            'razon_social' => 'Cliente 1',
            'ruta_id' => $ruta->id,
        ]);
        $cliente->delete();

        $this->delete(route('gestion.rutas.destroy', $ruta))->assertRedirect();

        $this->assertModelExists($ruta);
        $this->assertSoftDeleted($cliente);
    }

    public function test_elimina_una_ruta_sin_clientes(): void
    {
        $ruta = $this->crearEn(Ruta::class, $this->tenant, ['ruta' => 'Norte']);

        $this->delete(route('gestion.rutas.destroy', $ruta))->assertRedirect(route('gestion.rutas.index'));

        $this->assertModelMissing($ruta);
    }

    public function test_no_toca_rutas_de_otro_tenant_y_exige_permiso(): void
    {
        $ajena = $this->crearEn(Ruta::class, Tenant::factory()->create(), ['ruta' => 'Ajena']);

        $this->put(route('gestion.rutas.update', $ajena), ['ruta' => 'X'])->assertNotFound();

        $this->actingAs($this->personalConPermisos($this->tenant, ['rutas.ver']));
        $this->get(route('gestion.rutas.index'))->assertOk();
        $this->post(route('gestion.rutas.store'), ['ruta' => 'Sur'])->assertForbidden();
    }
}
