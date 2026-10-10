<?php

namespace Tests\Feature\Gestion;

use App\Actions\Stock\MoverStock;
use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\DetalleStock;
use App\Models\Medida;
use App\Models\Producto;
use App\Models\StockBodega;
use App\Models\StockInicial;
use App\Models\SubCategoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El stock inicial genera un ingreso en los movimientos de la bodega y lo sigue
 * cuando se corrige, se elimina o se restaura.
 */
class StockInicialMovimientosTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    /** @var array{medida: Medida, bodega: Bodega, categoria: Categoria, subcategoria: SubCategoria} */
    private array $catalogos;

    private Producto $producto;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararGestion();
        $this->usuario = $this->administradorDe($this->tenant);
        $this->actingAs($this->usuario);

        $this->catalogos = $this->catalogosBasicosDe($this->tenant);
        $this->producto = $this->crearProductoEn($this->tenant, $this->catalogos);
    }

    private function crearStockInicial(float|int|string $cantidad): StockInicial
    {
        $this->post(route('gestion.stock-inicial.store'), [
            'producto_id' => $this->producto->id,
            'bodega_id' => $this->catalogos['bodega']->id,
            'cantidad' => $cantidad,
        ])->assertSessionHasNoErrors();

        return StockInicial::firstOrFail();
    }

    private function stock(): StockBodega
    {
        return StockBodega::query()
            ->where('producto_id', $this->producto->id)
            ->where('bodega_id', $this->catalogos['bodega']->id)
            ->firstOrFail();
    }

    private function salida(float $cantidad): void
    {
        app(MoverStock::class)->registrar(
            $this->producto->id,
            $this->catalogos['bodega']->id,
            $this->usuario->id,
            'SALIDA',
            $cantidad,
            'Venta de prueba',
        );
    }

    public function test_crear_un_stock_inicial_registra_su_ingreso_en_la_bodega(): void
    {
        $stockInicial = $this->crearStockInicial(25.5);

        $this->assertEquals(25.5, $this->stock()->entradas);
        $this->assertEquals(25.5, $this->stock()->stock);

        $movimiento = DetalleStock::firstOrFail();
        $this->assertSame('INGRESO', $movimiento->tipo);
        $this->assertEquals(25.5, $movimiento->cantidad);
        $this->assertSame('Stock inicial', $movimiento->descripcion);
        $this->assertSame($this->usuario->id, $movimiento->user_id);
        $this->assertTrue($movimiento->origen->is($stockInicial));
        $this->assertSame('stock_inicial', $movimiento->origen_type);
        $this->assertFalse($movimiento->esManual());
    }

    public function test_editar_la_cantidad_corrige_el_mismo_movimiento_sin_duplicarlo(): void
    {
        $stockInicial = $this->crearStockInicial(100);
        $movimientoId = DetalleStock::firstOrFail()->id;

        $this->put(route('gestion.stock-inicial.update', $stockInicial), ['cantidad' => 80])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, DetalleStock::count());
        $this->assertSame(0, DetalleStock::onlyTrashed()->count());
        $movimiento = DetalleStock::firstOrFail();
        $this->assertSame($movimientoId, $movimiento->id);
        $this->assertEquals(80, $movimiento->cantidad);
        $this->assertStringContainsString('corregido de 100 a 80 por '.$this->usuario->name, $movimiento->descripcion);
        $this->assertEquals(80, $this->stock()->entradas);
        $this->assertEquals(80, $this->stock()->stock);
    }

    public function test_guardar_sin_cambios_no_toca_el_movimiento(): void
    {
        $stockInicial = $this->crearStockInicial(100);

        $this->put(route('gestion.stock-inicial.update', $stockInicial), ['cantidad' => 100]);

        $this->assertSame('Stock inicial', DetalleStock::firstOrFail()->descripcion);
    }

    public function test_eliminar_el_stock_inicial_anula_su_movimiento(): void
    {
        $stockInicial = $this->crearStockInicial(100);

        $this->delete(route('gestion.stock-inicial.destroy', $stockInicial))
            ->assertRedirect(route('gestion.stock-inicial.index'));

        $this->assertSoftDeleted($stockInicial);
        $this->assertSame(0, DetalleStock::count());
        $this->assertSame(1, DetalleStock::onlyTrashed()->count());
        $this->assertEquals(0, $this->stock()->stock);
    }

    public function test_restaurar_el_stock_inicial_vuelve_a_registrar_su_ingreso(): void
    {
        $stockInicial = $this->crearStockInicial(100);
        $this->delete(route('gestion.stock-inicial.destroy', $stockInicial));

        $this->post(route('gestion.stock-inicial.restore', $stockInicial))
            ->assertRedirect(route('gestion.stock-inicial.index'));

        $this->assertNotSoftDeleted($stockInicial);
        $this->assertSame(1, DetalleStock::count());
        $this->assertEquals(100, $this->stock()->stock);
    }

    public function test_no_se_puede_reducir_un_stock_inicial_por_debajo_de_lo_que_ya_salio(): void
    {
        $stockInicial = $this->crearStockInicial(100);
        $this->salida(70);

        $this->put(route('gestion.stock-inicial.update', $stockInicial), ['cantidad' => 50])
            ->assertSessionHasErrors('cantidad');

        // Ni la cantidad del stock inicial ni el inventario cambiaron.
        $this->assertEquals(100, $stockInicial->refresh()->cantidad);
        $this->assertEquals(30, $this->stock()->stock);

        $this->put(route('gestion.stock-inicial.update', $stockInicial), ['cantidad' => 70])
            ->assertSessionHasNoErrors();
        $this->assertEquals(0, $this->stock()->stock);
    }

    public function test_se_puede_aumentar_un_stock_inicial_aunque_ya_haya_salidas(): void
    {
        $stockInicial = $this->crearStockInicial(100);
        $this->salida(70);

        $this->put(route('gestion.stock-inicial.update', $stockInicial), ['cantidad' => 120])
            ->assertSessionHasNoErrors();

        $this->assertEquals(50, $this->stock()->stock);
    }

    public function test_no_se_puede_eliminar_un_stock_inicial_cuya_mercancia_ya_salio(): void
    {
        $stockInicial = $this->crearStockInicial(100);
        $this->salida(70);

        $this->delete(route('gestion.stock-inicial.destroy', $stockInicial))->assertRedirect();

        $this->assertNotSoftDeleted($stockInicial);
        $this->assertSame(2, DetalleStock::count());
        $this->assertSame(0, DetalleStock::onlyTrashed()->count());
        $this->assertEquals(30, $this->stock()->stock);
    }

    public function test_el_movimiento_aparece_en_el_detalle_y_no_se_corrige_desde_alli(): void
    {
        $this->crearStockInicial(100);
        $movimiento = DetalleStock::firstOrFail();

        $this->get(route('gestion.stock-bodegas.show', $this->stock()))
            ->assertInertia(fn ($page) => $page
                ->has('movimientos.data', 1)
                ->where('movimientos.data.0.origen', 'Stock inicial')
                ->where('movimientos.data.0.editable', false));

        $this->put(route('gestion.stock-bodegas.movimientos.update', [$this->stock(), $movimiento]), [
            'tipo' => 'INGRESO',
            'cantidad' => 1,
            'descripcion' => 'x',
        ])->assertForbidden();
        $this->delete(route('gestion.stock-bodegas.movimientos.destroy', [$this->stock(), $movimiento]))->assertForbidden();
    }

    public function test_un_stock_inicial_en_cero_no_genera_movimiento(): void
    {
        $stockInicial = $this->crearStockInicial(0);

        $this->assertSame(0, DetalleStock::count());

        $this->put(route('gestion.stock-inicial.update', $stockInicial), ['cantidad' => 5]);
        $this->assertSame(1, DetalleStock::count());

        $this->put(route('gestion.stock-inicial.update', $stockInicial), ['cantidad' => 0]);
        $this->assertSame(0, DetalleStock::count());
        $this->assertEquals(0, $this->stock()->stock);
    }

    public function test_sincronizar_un_origen_con_otro_producto_o_bodega_anula_el_movimiento_anterior(): void
    {
        $origen = $this->crearStockInicial(10);
        $otraBodega = $this->crearEn(Bodega::class, $this->tenant, ['nombre_bodega' => 'Secundaria']);

        app(MoverStock::class)->sincronizar($origen, [
            'producto_id' => $this->producto->id,
            'bodega_id' => $otraBodega->id,
            'tipo' => 'INGRESO',
            'cantidad' => 10,
        ], $this->usuario->id, 'Stock inicial');

        $this->assertEquals(0, $this->stock()->stock);
        $this->assertEquals(10, StockBodega::where('bodega_id', $otraBodega->id)->firstOrFail()->stock);
        $this->assertSame(1, DetalleStock::count());
        $this->assertSame(1, DetalleStock::onlyTrashed()->count());
    }
}
