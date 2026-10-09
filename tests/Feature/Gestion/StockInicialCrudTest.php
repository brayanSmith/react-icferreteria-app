<?php

namespace Tests\Feature\Gestion;

use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\Medida;
use App\Models\Producto;
use App\Models\StockInicial;
use App\Models\SubCategoria;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockInicialCrudTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    /** @var array{medida: Medida, bodega: Bodega, categoria: Categoria, subcategoria: SubCategoria} */
    private array $catalogos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararGestion();
        $this->actingAs($this->administradorDe($this->tenant));
        $this->catalogos = $this->catalogosBasicosDe($this->tenant);
    }

    private function stockEn(Producto $producto, ?Bodega $bodega = null, string $cantidad = '10'): StockInicial
    {
        return $this->crearEn(StockInicial::class, $this->tenant, [
            'producto_id' => $producto->id,
            'bodega_id' => ($bodega ?? $this->catalogos['bodega'])->id,
            'cantidad' => $cantidad,
        ]);
    }

    public function test_registra_el_stock_inicial_de_un_producto_en_una_bodega(): void
    {
        $producto = $this->crearProductoEn($this->tenant, $this->catalogos);

        $this->post(route('gestion.stock-inicial.store'), [
            'producto_id' => $producto->id,
            'bodega_id' => $this->catalogos['bodega']->id,
            'cantidad' => '25.5',
        ])->assertRedirect(route('gestion.stock-inicial.index'));

        $this->assertDatabaseHas('stock_inicials', [
            'tenant_id' => $this->tenant->id,
            'producto_id' => $producto->id,
            'cantidad' => 25.5,
        ]);
    }

    public function test_valida_la_cantidad(): void
    {
        $producto = $this->crearProductoEn($this->tenant, $this->catalogos);
        $datos = ['producto_id' => $producto->id, 'bodega_id' => $this->catalogos['bodega']->id];

        $this->post(route('gestion.stock-inicial.store'), [...$datos, 'cantidad' => '-1'])->assertSessionHasErrors('cantidad');
        $this->post(route('gestion.stock-inicial.store'), [...$datos, 'cantidad' => 'mucho'])->assertSessionHasErrors('cantidad');
        $this->post(route('gestion.stock-inicial.store'), $datos)->assertSessionHasErrors('cantidad');
    }

    public function test_solo_hay_un_stock_inicial_por_producto_y_bodega(): void
    {
        $producto = $this->crearProductoEn($this->tenant, $this->catalogos);
        $this->stockEn($producto);

        $this->post(route('gestion.stock-inicial.store'), [
            'producto_id' => $producto->id,
            'bodega_id' => $this->catalogos['bodega']->id,
            'cantidad' => '5',
        ])->assertSessionHasErrors('producto_id');

        $this->assertStringContainsString('edítalo', session('errors')->first('producto_id'));
        $this->assertDatabaseCount('stock_inicials', 1);
    }

    public function test_el_mismo_producto_puede_tener_stock_en_otra_bodega(): void
    {
        $producto = $this->crearProductoEn($this->tenant, $this->catalogos);
        $this->stockEn($producto);
        $otraBodega = $this->crearEn(Bodega::class, $this->tenant, ['nombre_bodega' => 'Secundaria']);

        $this->post(route('gestion.stock-inicial.store'), [
            'producto_id' => $producto->id,
            'bodega_id' => $otraBodega->id,
            'cantidad' => '5',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('stock_inicials', 2);
    }

    public function test_un_stock_eliminado_sigue_reservado_y_avisa_que_se_restaure(): void
    {
        $producto = $this->crearProductoEn($this->tenant, $this->catalogos);
        $this->stockEn($producto)->delete();

        $this->post(route('gestion.stock-inicial.store'), [
            'producto_id' => $producto->id,
            'bodega_id' => $this->catalogos['bodega']->id,
            'cantidad' => '5',
        ])->assertSessionHasErrors('producto_id');

        $this->assertStringContainsString('restáuralo', session('errors')->first('producto_id'));
    }

    public function test_no_acepta_productos_ni_bodegas_de_otro_tenant_o_eliminados(): void
    {
        $ajeno = $this->catalogosBasicosDe(Tenant::factory()->create());
        $productoAjeno = $this->crearProductoEn(Tenant::find($ajeno['bodega']->tenant_id), $ajeno);

        $this->post(route('gestion.stock-inicial.store'), [
            'producto_id' => $productoAjeno->id,
            'bodega_id' => $ajeno['bodega']->id,
            'cantidad' => '1',
        ])->assertSessionHasErrors(['producto_id', 'bodega_id']);

        $producto = $this->crearProductoEn($this->tenant, $this->catalogos);
        $producto->delete();

        $this->post(route('gestion.stock-inicial.store'), [
            'producto_id' => $producto->id,
            'bodega_id' => $this->catalogos['bodega']->id,
            'cantidad' => '1',
        ])->assertSessionHasErrors('producto_id');

        $this->assertDatabaseCount('stock_inicials', 0);
    }

    public function test_al_editar_solo_cambia_la_cantidad(): void
    {
        $producto = $this->crearProductoEn($this->tenant, $this->catalogos);
        $otroProducto = $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'P-002']);
        $stock = $this->stockEn($producto);

        $this->put(route('gestion.stock-inicial.update', $stock), [
            'cantidad' => '99',
            'producto_id' => $otroProducto->id,
        ])->assertRedirect(route('gestion.stock-inicial.index'));

        $stock->refresh();
        $this->assertEquals(99, $stock->cantidad);
        $this->assertSame($producto->id, $stock->producto_id);
    }

    public function test_el_listado_busca_por_producto_y_separa_activos_de_eliminados(): void
    {
        $martillo = $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'A', 'nombre_producto' => 'Martillo']);
        $serrucho = $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'B', 'nombre_producto' => 'Serrucho']);
        $cincel = $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'C', 'nombre_producto' => 'Cincel']);
        $this->stockEn($martillo);
        $this->stockEn($serrucho);
        $this->stockEn($cincel)->delete();

        $this->get(route('gestion.stock-inicial.index', ['buscar' => 'Martillo']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gestion/stock-inicial/index')
                ->has('stocks.data', 1)
                ->where('stocks.data.0.nombre_producto', 'Martillo')
                ->where('stocks.data.0.bodega', 'Principal'));

        $this->get(route('gestion.stock-inicial.index', ['eliminados' => 1]))
            ->assertInertia(fn ($page) => $page
                ->has('stocks.data', 1)
                ->where('stocks.data.0.nombre_producto', 'Cincel'));
    }

    public function test_el_listado_trae_productos_y_bodegas_para_el_formulario(): void
    {
        $this->crearProductoEn($this->tenant, $this->catalogos);

        $this->get(route('gestion.stock-inicial.index'))
            ->assertInertia(fn ($page) => $page
                ->where('productos.0.nombre', 'P-001 - Martillo')
                ->where('bodegas.0.nombre', 'Principal'));
    }

    public function test_un_producto_eliminado_sigue_apareciendo_en_su_historial(): void
    {
        $producto = $this->crearProductoEn($this->tenant, $this->catalogos);
        $this->stockEn($producto);
        $producto->delete();

        $this->get(route('gestion.stock-inicial.index'))
            ->assertInertia(fn ($page) => $page->where('stocks.data.0.nombre_producto', 'Martillo'));
    }

    public function test_elimina_de_forma_logica_y_restaura(): void
    {
        $stock = $this->stockEn($this->crearProductoEn($this->tenant, $this->catalogos));

        $this->delete(route('gestion.stock-inicial.destroy', $stock))->assertRedirect(route('gestion.stock-inicial.index'));
        $this->assertSoftDeleted($stock);

        $this->post(route('gestion.stock-inicial.restore', $stock))->assertRedirect(route('gestion.stock-inicial.index'));
        $this->assertNotSoftDeleted($stock);
    }

    public function test_una_bodega_con_stock_inicial_no_se_puede_eliminar(): void
    {
        // Los productos viven en la bodega "Principal"; "Vacía" solo tiene stock inicial.
        $bodegaVacia = $this->crearEn(Bodega::class, $this->tenant, ['nombre_bodega' => 'Vacía']);
        $this->stockEn($this->crearProductoEn($this->tenant, $this->catalogos), $bodegaVacia);

        $this->delete(route('gestion.bodegas.destroy', $bodegaVacia))->assertRedirect();

        $this->assertNotSoftDeleted($bodegaVacia);
    }

    public function test_no_toca_registros_de_otro_tenant(): void
    {
        $otroTenant = Tenant::factory()->create();
        $catalogosAjenos = $this->catalogosBasicosDe($otroTenant);
        $productoAjeno = $this->crearProductoEn($otroTenant, $catalogosAjenos);
        $ajeno = $this->crearEn(StockInicial::class, $otroTenant, [
            'producto_id' => $productoAjeno->id,
            'bodega_id' => $catalogosAjenos['bodega']->id,
            'cantidad' => '3',
        ]);

        $this->put(route('gestion.stock-inicial.update', $ajeno), ['cantidad' => '1'])->assertNotFound();
        $this->delete(route('gestion.stock-inicial.destroy', $ajeno))->assertNotFound();
        $this->post(route('gestion.stock-inicial.restore', $ajeno))->assertNotFound();
    }

    public function test_exige_permiso_por_accion(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['stock_inicial.ver']));

        $this->get(route('gestion.stock-inicial.index'))->assertOk();
        $this->post(route('gestion.stock-inicial.store'), ['cantidad' => '1'])->assertForbidden();
    }
}
