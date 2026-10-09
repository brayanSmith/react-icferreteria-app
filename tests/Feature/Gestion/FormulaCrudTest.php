<?php

namespace Tests\Feature\Gestion;

use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\Formula;
use App\Models\Medida;
use App\Models\Producto;
use App\Models\SubCategoria;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FormulaCrudTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    /** @var array{medida: Medida, bodega: Bodega, categoria: Categoria, subcategoria: SubCategoria} */
    private array $catalogos;

    private Producto $resina;

    private Producto $pigmento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararGestion();
        $this->actingAs($this->administradorDe($this->tenant));
        $this->catalogos = $this->catalogosBasicosDe($this->tenant);
        $this->resina = $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'RES', 'nombre_producto' => 'Resina', 'categoria_producto' => 'MATERIA_PRIMA']);
        $this->pigmento = $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'PIG', 'nombre_producto' => 'Pigmento', 'categoria_producto' => 'MATERIA_PRIMA']);
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function datos(array $sobrescribir = []): array
    {
        return [
            'nombre_formula' => 'Pintura blanca',
            'descripcion_formula' => 'Base para interiores',
            'detalles' => [
                ['producto_id' => $this->resina->id, 'cantidad_producto' => '10.5'],
                ['producto_id' => $this->pigmento->id, 'cantidad_producto' => '2'],
            ],
            ...$sobrescribir,
        ];
    }

    private function crearFormula(string $nombre = 'Existente', ?Tenant $tenant = null): Formula
    {
        $formula = $this->crearEn(Formula::class, $tenant ?? $this->tenant, ['nombre_formula' => $nombre]);
        $formula->detalleFormulas()->create(['producto_id' => $this->resina->id, 'cantidad_producto' => 5]);

        return $formula;
    }

    public function test_guarda_la_formula_con_su_detalle_en_una_sola_operacion(): void
    {
        $this->post(route('gestion.formulas.store'), $this->datos())
            ->assertRedirect(route('gestion.formulas.index'));

        $formula = Formula::firstOrFail();

        $this->assertSame($this->tenant->id, $formula->tenant_id);
        $this->assertSame('Pintura blanca', $formula->nombre_formula);
        $this->assertCount(2, $formula->detalleFormulas);
        $this->assertDatabaseHas('detalle_formulas', [
            'formula_id' => $formula->id,
            'producto_id' => $this->resina->id,
            'cantidad_producto' => 10.5,
        ]);
    }

    public function test_exige_al_menos_un_ingrediente(): void
    {
        $this->post(route('gestion.formulas.store'), $this->datos(['detalles' => []]))
            ->assertSessionHasErrors('detalles');
        $this->post(route('gestion.formulas.store'), ['nombre_formula' => 'Sin detalle'])
            ->assertSessionHasErrors('detalles');

        $this->assertDatabaseCount('formulas', 0);
    }

    public function test_si_un_ingrediente_es_invalido_no_se_guarda_nada(): void
    {
        $this->post(route('gestion.formulas.store'), $this->datos([
            'detalles' => [
                ['producto_id' => $this->resina->id, 'cantidad_producto' => '3'],
                ['producto_id' => $this->pigmento->id, 'cantidad_producto' => '0'],
            ],
        ]))->assertSessionHasErrors('detalles.1.cantidad_producto');

        $this->assertDatabaseCount('formulas', 0);
        $this->assertDatabaseCount('detalle_formulas', 0);
    }

    public function test_un_fallo_al_guardar_el_detalle_deshace_tambien_la_formula(): void
    {
        // Se fuerza un error de base de datos en el detalle para comprobar la transacción.
        DB::statement('DROP TABLE detalle_formulas');

        try {
            $this->withoutExceptionHandling()->post(route('gestion.formulas.store'), $this->datos());
            $this->fail('Se esperaba un error al guardar el detalle.');
        } catch (\Throwable) {
            $this->assertDatabaseCount('formulas', 0);
        }
    }

    public function test_un_producto_no_puede_repetirse_en_la_misma_formula(): void
    {
        $this->post(route('gestion.formulas.store'), $this->datos([
            'detalles' => [
                ['producto_id' => $this->resina->id, 'cantidad_producto' => '1'],
                ['producto_id' => $this->resina->id, 'cantidad_producto' => '2'],
            ],
        ]))->assertSessionHasErrors(['detalles.0.producto_id', 'detalles.1.producto_id']);
    }

    public function test_valida_la_cantidad_de_cada_ingrediente(): void
    {
        foreach (['', '-1', 'mucho'] as $cantidad) {
            $this->post(route('gestion.formulas.store'), $this->datos([
                'detalles' => [['producto_id' => $this->resina->id, 'cantidad_producto' => $cantidad]],
            ]))->assertSessionHasErrors('detalles.0.cantidad_producto');
        }
    }

    public function test_solo_acepta_productos_de_tipo_materia_prima(): void
    {
        $terminado = $this->crearProductoEn($this->tenant, $this->catalogos, [
            'codigo_producto' => 'TER',
            'categoria_producto' => 'PRODUCTO_TERMINADO',
        ]);

        $this->post(route('gestion.formulas.store'), $this->datos([
            'detalles' => [
                ['producto_id' => $this->resina->id, 'cantidad_producto' => '1'],
                ['producto_id' => $terminado->id, 'cantidad_producto' => '1'],
            ],
        ]))->assertSessionHasErrors(['detalles.1.producto_id' => 'Elige un producto de tipo materia prima.']);

        $this->assertDatabaseCount('formulas', 0);
    }

    public function test_el_formulario_solo_ofrece_materia_prima(): void
    {
        $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'TER', 'categoria_producto' => 'PRODUCTO_TERMINADO']);
        $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'GAS', 'categoria_producto' => 'GASTOS']);

        $this->get(route('gestion.formulas.index'))
            ->assertInertia(fn ($page) => $page
                ->has('productos', 2)
                ->where('productos.0.nombre', 'PIG - Pigmento')
                ->where('productos.1.nombre', 'RES - Resina'));
    }

    public function test_una_formula_antigua_con_un_producto_que_no_es_materia_prima_se_sigue_mostrando_pero_no_se_puede_volver_a_guardar(): void
    {
        $terminado = $this->crearProductoEn($this->tenant, $this->catalogos, [
            'codigo_producto' => 'TER',
            'nombre_producto' => 'Pintura lista',
            'categoria_producto' => 'PRODUCTO_TERMINADO',
        ]);
        $formula = $this->crearFormula('Antigua');
        $formula->detalleFormulas()->create(['producto_id' => $terminado->id, 'cantidad_producto' => 2]);

        $this->get(route('gestion.formulas.index'))
            ->assertInertia(fn ($page) => $page->where('formulas.data.0.detalles.1.producto', 'TER - Pintura lista'));

        $this->put(route('gestion.formulas.update', $formula), $this->datos([
            'nombre_formula' => 'Antigua',
            'detalles' => [['producto_id' => $terminado->id, 'cantidad_producto' => '2']],
        ]))->assertSessionHasErrors('detalles.0.producto_id');
    }

    public function test_no_acepta_productos_de_otro_tenant_ni_eliminados(): void
    {
        $otroTenant = Tenant::factory()->create();
        $ajeno = $this->crearProductoEn($otroTenant, $this->catalogosBasicosDe($otroTenant), ['categoria_producto' => 'MATERIA_PRIMA']);
        $eliminado = $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'OLD', 'categoria_producto' => 'MATERIA_PRIMA']);
        $eliminado->delete();

        $this->post(route('gestion.formulas.store'), $this->datos([
            'detalles' => [
                ['producto_id' => $ajeno->id, 'cantidad_producto' => '1'],
                ['producto_id' => $eliminado->id, 'cantidad_producto' => '1'],
            ],
        ]))->assertSessionHasErrors(['detalles.0.producto_id', 'detalles.1.producto_id']);

        $this->assertDatabaseCount('formulas', 0);
    }

    public function test_el_nombre_es_unico_en_el_tenant_pero_repetible_en_otro(): void
    {
        $this->crearFormula('Pintura blanca');

        $this->post(route('gestion.formulas.store'), $this->datos())->assertSessionHasErrors('nombre_formula');

        $otroTenant = Tenant::factory()->create();
        $this->actingAs($this->administradorDe($otroTenant));
        $productoPropio = $this->crearProductoEn($otroTenant, $this->catalogosBasicosDe($otroTenant), ['categoria_producto' => 'MATERIA_PRIMA']);

        $this->post(route('gestion.formulas.store'), $this->datos([
            'detalles' => [['producto_id' => $productoPropio->id, 'cantidad_producto' => '1']],
        ]))->assertSessionHasNoErrors();
    }

    public function test_al_actualizar_sincroniza_el_detalle(): void
    {
        $formula = $this->crearFormula('Pintura blanca');
        $otroProducto = $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'SOL', 'nombre_producto' => 'Solvente', 'categoria_producto' => 'MATERIA_PRIMA']);

        // La resina cambia de cantidad, el pigmento no estaba y se agrega, y se suma un tercero.
        $this->put(route('gestion.formulas.update', $formula), $this->datos([
            'detalles' => [
                ['producto_id' => $this->resina->id, 'cantidad_producto' => '99'],
                ['producto_id' => $otroProducto->id, 'cantidad_producto' => '4'],
            ],
        ]))->assertRedirect(route('gestion.formulas.index'));

        $formula->refresh();
        $this->assertCount(2, $formula->detalleFormulas);
        $this->assertEquals(99, $formula->detalleFormulas->firstWhere('producto_id', $this->resina->id)->cantidad_producto);
        $this->assertNotNull($formula->detalleFormulas->firstWhere('producto_id', $otroProducto->id));
    }

    public function test_al_actualizar_elimina_los_ingredientes_que_ya_no_vienen(): void
    {
        $formula = $this->crearFormula('Pintura blanca');
        $formula->detalleFormulas()->create(['producto_id' => $this->pigmento->id, 'cantidad_producto' => 1]);

        $this->put(route('gestion.formulas.update', $formula), $this->datos([
            'detalles' => [['producto_id' => $this->pigmento->id, 'cantidad_producto' => '7']],
        ]))->assertSessionHasNoErrors();

        $detalles = $formula->refresh()->detalleFormulas;
        $this->assertCount(1, $detalles);
        $this->assertSame($this->pigmento->id, $detalles->first()->producto_id);
    }

    public function test_actualizar_conserva_su_propio_nombre(): void
    {
        $formula = $this->crearFormula('Pintura blanca');

        $this->put(route('gestion.formulas.update', $formula), $this->datos())->assertSessionHasNoErrors();
    }

    public function test_el_listado_trae_los_ingredientes_y_busca_por_nombre(): void
    {
        $this->crearFormula('Pintura blanca');
        $this->crearFormula('Barniz');
        $this->crearFormula('Pintura ajena', Tenant::factory()->create());

        $this->get(route('gestion.formulas.index', ['buscar' => 'Pintura']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gestion/formulas/index')
                ->has('formulas.data', 1)
                ->where('formulas.data.0.nombre_formula', 'Pintura blanca')
                ->where('formulas.data.0.detalles.0.producto', 'RES - Resina')
                ->where('formulas.data.0.detalles.0.cantidad_producto', 5)
                ->has('productos', 2));
    }

    public function test_un_producto_eliminado_sigue_apareciendo_en_las_formulas_que_ya_lo_usaban(): void
    {
        $formula = $this->crearFormula();
        $this->resina->delete();

        $this->get(route('gestion.formulas.index'))
            ->assertInertia(fn ($page) => $page->where('formulas.data.0.detalles.0.producto', 'RES - Resina'));
    }

    public function test_elimina_la_formula_con_su_detalle(): void
    {
        $formula = $this->crearFormula();

        $this->delete(route('gestion.formulas.destroy', $formula))->assertRedirect(route('gestion.formulas.index'));

        $this->assertModelMissing($formula);
        $this->assertDatabaseCount('detalle_formulas', 0);
    }

    public function test_no_elimina_una_formula_con_producciones(): void
    {
        $formula = $this->crearFormula();
        $responsable = auth()->id();

        DB::table('produccions')->insert([
            'tenant_id' => $this->tenant->id,
            'numero' => 1,
            'formula_id' => $formula->id,
            'bodega_id' => $this->catalogos['bodega']->id,
            'cantidad' => 10,
            'lote' => 'L1',
            'ph' => 7,
            'biscocidad' => 1,
            'homogeneidad' => 1,
            'fecha_produccion' => '2026-01-01',
            'fecha_caducidad' => '2027-01-01',
            'responsable_lote_id' => $responsable,
            'responsable_cc_id' => $responsable,
        ]);

        $this->delete(route('gestion.formulas.destroy', $formula))->assertRedirect();

        $this->assertModelExists($formula);
        $this->assertDatabaseCount('produccions', 1);
    }

    public function test_no_toca_formulas_de_otro_tenant(): void
    {
        $ajena = $this->crearFormula('Ajena', Tenant::factory()->create());

        $this->put(route('gestion.formulas.update', $ajena), $this->datos())->assertNotFound();
        $this->delete(route('gestion.formulas.destroy', $ajena))->assertNotFound();
    }

    public function test_exige_permiso_por_accion(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['formulas.ver']));

        $this->get(route('gestion.formulas.index'))->assertOk();
        $this->post(route('gestion.formulas.store'), $this->datos())->assertForbidden();
    }
}
