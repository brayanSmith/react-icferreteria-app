<?php

namespace Tests\Feature\Gestion;

use App\Models\Medida;
use App\Models\Producto;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedidaCrudTest extends TestCase
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
        return ['nombre_medida' => 'Kilogramo', 'tipo_medida' => 'PESO', 'descripcion_medida' => 'Peso en kilos', ...$sobrescribir];
    }

    public function test_crea_una_medida(): void
    {
        $this->post(route('gestion.medidas.store'), $this->datos())
            ->assertRedirect(route('gestion.medidas.index'));

        $this->assertDatabaseHas('medidas', ['tenant_id' => $this->tenant->id, 'nombre_medida' => 'Kilogramo']);
    }

    public function test_el_nombre_es_unico_en_el_tenant_pero_repetible_en_otro(): void
    {
        $this->crearEn(Medida::class, $this->tenant, $this->datos());

        $this->post(route('gestion.medidas.store'), $this->datos())->assertSessionHasErrors('nombre_medida');

        $this->actingAs($this->administradorDe(Tenant::factory()->create()));
        $this->post(route('gestion.medidas.store'), $this->datos())->assertSessionHasNoErrors();
    }

    public function test_valida_el_tipo_de_medida(): void
    {
        $this->post(route('gestion.medidas.store'), $this->datos(['tipo_medida' => 'COLOR']))
            ->assertSessionHasErrors('tipo_medida');
    }

    public function test_actualiza_una_medida(): void
    {
        $medida = $this->crearEn(Medida::class, $this->tenant, $this->datos());

        $this->put(route('gestion.medidas.update', $medida), $this->datos(['nombre_medida' => 'Gramo']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Gramo', $medida->refresh()->nombre_medida);
    }

    public function test_el_listado_cuenta_los_productos_de_cada_medida(): void
    {
        $catalogos = $this->catalogosBasicosDe($this->tenant);
        $this->crearEn(Producto::class, $this->tenant, [
            'codigo_producto' => 'P1',
            'nombre_producto' => 'Martillo',
            'medida_id' => $catalogos['medida']->id,
            'categoria_id' => $catalogos['categoria']->id,
            'sub_categoria_id' => $catalogos['subcategoria']->id,
        ]);

        $this->get(route('gestion.medidas.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gestion/medidas/index')
                ->where('medidas.data.0.productos_count', 1));
    }

    public function test_no_elimina_una_medida_que_usan_productos(): void
    {
        $catalogos = $this->catalogosBasicosDe($this->tenant);
        $this->crearEn(Producto::class, $this->tenant, [
            'codigo_producto' => 'P1',
            'nombre_producto' => 'Martillo',
            'medida_id' => $catalogos['medida']->id,
            'categoria_id' => $catalogos['categoria']->id,
            'sub_categoria_id' => $catalogos['subcategoria']->id,
        ]);

        $this->delete(route('gestion.medidas.destroy', $catalogos['medida']))->assertRedirect();

        $this->assertModelExists($catalogos['medida']);
        $this->assertDatabaseCount('productos', 1);
    }

    public function test_elimina_una_medida_sin_uso(): void
    {
        $medida = $this->crearEn(Medida::class, $this->tenant, $this->datos());

        $this->delete(route('gestion.medidas.destroy', $medida))->assertRedirect(route('gestion.medidas.index'));

        $this->assertModelMissing($medida);
    }

    public function test_no_toca_medidas_de_otro_tenant(): void
    {
        $ajena = $this->crearEn(Medida::class, Tenant::factory()->create(), $this->datos());

        $this->put(route('gestion.medidas.update', $ajena), $this->datos())->assertNotFound();
        $this->delete(route('gestion.medidas.destroy', $ajena))->assertNotFound();
    }

    public function test_exige_permiso_por_accion(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['medidas.ver']));

        $this->get(route('gestion.medidas.index'))->assertOk();
        $this->post(route('gestion.medidas.store'), $this->datos())->assertForbidden();
    }
}
