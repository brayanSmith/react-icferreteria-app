<?php

namespace Tests\Feature\Gestion;

use App\Actions\Stock\MoverStock;
use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\DetalleStock;
use App\Models\Medida;
use App\Models\Producto;
use App\Models\StockBodega;
use App\Models\SubCategoria;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockBodegaCrudTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function movimiento(string $tipo, float|int $cantidad, array $extra = []): array
    {
        return [
            'producto_id' => $this->producto->id,
            'bodega_id' => $this->catalogos['bodega']->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'descripcion' => 'Compra de prueba',
            ...$extra,
        ];
    }

    private function registrar(string $tipo, float|int $cantidad): DetalleStock
    {
        return app(MoverStock::class)->registrar(
            $this->producto->id,
            $this->catalogos['bodega']->id,
            $this->usuario->id,
            $tipo,
            (float) $cantidad,
            'Movimiento de prueba',
        );
    }

    private function stock(): StockBodega
    {
        return StockBodega::query()
            ->where('producto_id', $this->producto->id)
            ->where('bodega_id', $this->catalogos['bodega']->id)
            ->firstOrFail();
    }

    public function test_el_primer_ingreso_crea_las_existencias_y_deja_su_movimiento(): void
    {
        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('INGRESO', 10))
            ->assertSessionHasNoErrors();

        $stock = $this->stock();
        $this->assertEquals(10, $stock->entradas);
        $this->assertEquals(0, $stock->salidas);
        $this->assertEquals(10, $stock->stock);
        $this->assertSame($this->tenant->id, $stock->tenant_id);

        $this->assertDatabaseHas('detalle_stocks', [
            'tenant_id' => $this->tenant->id,
            'stock_bodega_id' => $stock->id,
            'producto_id' => $this->producto->id,
            'user_id' => $this->usuario->id,
            'tipo' => 'INGRESO',
            'origen_type' => null,
            'origen_id' => null,
            'descripcion' => 'Compra de prueba',
        ]);
    }

    public function test_las_salidas_descuentan_y_los_ingresos_siguientes_reutilizan_las_existencias(): void
    {
        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('INGRESO', 10));
        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('SALIDA', 3.5));
        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('INGRESO', 2));

        $this->assertSame(1, StockBodega::count());
        $this->assertSame(3, DetalleStock::count());
        $this->assertEquals(12, $this->stock()->entradas);
        $this->assertEquals(3.5, $this->stock()->salidas);
        $this->assertEquals(8.5, $this->stock()->stock);
    }

    public function test_una_salida_no_puede_dejar_el_stock_en_negativo(): void
    {
        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('INGRESO', 5));

        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('SALIDA', 6))
            ->assertSessionHasErrors('cantidad');

        $this->assertEquals(5, $this->stock()->stock);
        $this->assertSame(1, DetalleStock::count());
    }

    public function test_una_salida_sin_existencias_previas_no_deja_rastro(): void
    {
        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('SALIDA', 1))
            ->assertSessionHasErrors('cantidad');

        $this->assertSame(0, StockBodega::count());
        $this->assertSame(0, DetalleStock::count());
    }

    public function test_valida_los_datos_del_movimiento(): void
    {
        $this->post(route('gestion.stock-bodegas.store'), [])
            ->assertSessionHasErrors(['producto_id', 'bodega_id', 'tipo', 'cantidad', 'descripcion']);

        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('INGRESO', 0))->assertSessionHasErrors('cantidad');
        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('INGRESO', -4))->assertSessionHasErrors('cantidad');
        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('TRASPASO', 1))->assertSessionHasErrors('tipo');
    }

    public function test_no_acepta_productos_ni_bodegas_de_otro_tenant_o_eliminados(): void
    {
        $ajeno = Tenant::factory()->create();
        $catalogosAjenos = $this->catalogosBasicosDe($ajeno);
        $productoAjeno = $this->crearProductoEn($ajeno, $catalogosAjenos);

        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('INGRESO', 1, ['producto_id' => $productoAjeno->id]))
            ->assertSessionHasErrors('producto_id');
        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('INGRESO', 1, ['bodega_id' => $catalogosAjenos['bodega']->id]))
            ->assertSessionHasErrors('bodega_id');

        $this->producto->delete();
        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('INGRESO', 1))->assertSessionHasErrors('producto_id');
    }

    public function test_un_stock_eliminado_se_restaura_al_registrar_un_nuevo_movimiento(): void
    {
        $this->registrar('INGRESO', 4);
        $this->stock()->delete();

        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('INGRESO', 1))->assertSessionHasNoErrors();

        $this->assertSame(1, StockBodega::count());
        $this->assertEquals(5, $this->stock()->stock);
    }

    public function test_el_listado_trae_las_existencias_del_tenant_para_filtrar_en_el_navegador(): void
    {
        $this->registrar('INGRESO', 10);

        $otraBodega = $this->crearEn(Bodega::class, $this->tenant, ['nombre_bodega' => 'Secundaria']);
        app(MoverStock::class)->registrar($this->producto->id, $otraBodega->id, $this->usuario->id, 'INGRESO', 2.0, 'Traslado');

        $ajeno = Tenant::factory()->create();
        $catalogosAjenos = $this->catalogosBasicosDe($ajeno);
        $this->crearEn(StockBodega::class, $ajeno, [
            'bodega_id' => $catalogosAjenos['bodega']->id,
            'producto_id' => $this->crearProductoEn($ajeno, $catalogosAjenos)->id,
            'entradas' => 9,
            'salidas' => 0,
        ]);

        $this->get(route('gestion.stock-bodegas.index'))
            ->assertInertia(fn ($page) => $page
                ->component('gestion/stock-bodegas/index')
                ->has('stocks.data', 2)
                ->where('stocks.data', fn ($filas) => collect($filas)->pluck('bodega_id')->contains($otraBodega->id))
                ->has('productos', 1)
                ->has('bodegas', 2));
    }

    public function test_marca_el_stock_bajo_segun_la_alerta_del_producto(): void
    {
        $this->producto->update(['alerta_producto' => 5]);
        $this->registrar('INGRESO', 10);

        $this->get(route('gestion.stock-bodegas.index'))
            ->assertInertia(fn ($page) => $page->where('stocks.data.0.stock_bajo', false));

        $this->registrar('SALIDA', 6);

        $this->get(route('gestion.stock-bodegas.index'))
            ->assertInertia(fn ($page) => $page->where('stocks.data.0.stock_bajo', true));
    }

    public function test_el_detalle_muestra_el_historial_y_cuales_movimientos_se_pueden_corregir(): void
    {
        $manual = $this->registrar('INGRESO', 10);
        $this->stock()->detalles()->forceCreate([
            'producto_id' => $this->producto->id,
            'user_id' => $this->usuario->id,
            'tipo' => 'SALIDA',
            'cantidad' => 2,
            'tenant_id' => $this->tenant->id,
            'stock_bodega_id' => $this->stock()->id,
            'origen_type' => 'stock_inicial',
            'origen_id' => 99,
            'descripcion' => 'Pedido 15',
        ]);

        $this->get(route('gestion.stock-bodegas.show', $this->stock()))
            ->assertInertia(fn ($page) => $page
                ->component('gestion/stock-bodegas/show')
                ->where('stock.data.nombre_producto', 'Martillo')
                ->where('stock.data.bodega', 'Principal')
                ->has('movimientos.data', 2)
                ->where('movimientos.data.0.origen', 'Stock inicial')
                ->where('movimientos.data.0.editable', false)
                ->where('movimientos.data.1.id', $manual->id)
                ->where('movimientos.data.1.origen', 'Manual')
                ->where('movimientos.data.1.editable', true)
                ->where('movimientos.data.1.usuario', $this->usuario->name));
    }

    public function test_corregir_un_movimiento_ajusta_las_existencias(): void
    {
        $ingreso = $this->registrar('INGRESO', 10);
        $this->registrar('SALIDA', 8);

        // Subir el ingreso a 20 es válido aunque deshacerlo primero dejaría el stock en negativo.
        $this->put(route('gestion.stock-bodegas.movimientos.update', [$this->stock(), $ingreso]), [
            'tipo' => 'INGRESO',
            'cantidad' => 20,
            'descripcion' => 'Corregido',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(20, $this->stock()->entradas);
        $this->assertEquals(12, $this->stock()->stock);
        $this->assertSame('Corregido', $ingreso->refresh()->descripcion);
    }

    public function test_corregir_puede_cambiar_el_tipo_del_movimiento(): void
    {
        $this->registrar('INGRESO', 10);
        $salida = $this->registrar('SALIDA', 4);

        $this->put(route('gestion.stock-bodegas.movimientos.update', [$this->stock(), $salida]), [
            'tipo' => 'INGRESO',
            'cantidad' => 4,
            'descripcion' => 'Era un ingreso',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(14, $this->stock()->entradas);
        $this->assertEquals(0, $this->stock()->salidas);
    }

    public function test_no_permite_corregir_un_movimiento_si_deja_el_stock_en_negativo(): void
    {
        $ingreso = $this->registrar('INGRESO', 10);
        $this->registrar('SALIDA', 8);

        $this->put(route('gestion.stock-bodegas.movimientos.update', [$this->stock(), $ingreso]), [
            'tipo' => 'INGRESO',
            'cantidad' => 5,
            'descripcion' => 'Demasiado poco',
        ])->assertSessionHasErrors('cantidad');

        $this->assertEquals(10, $this->stock()->entradas);
        $this->assertSame('Movimiento de prueba', $ingreso->refresh()->descripcion);
    }

    public function test_anular_un_movimiento_deshace_su_efecto(): void
    {
        $this->registrar('INGRESO', 10);
        $salida = $this->registrar('SALIDA', 4);

        $this->delete(route('gestion.stock-bodegas.movimientos.destroy', [$this->stock(), $salida]))
            ->assertRedirect(route('gestion.stock-bodegas.show', $this->stock()));

        $this->assertSoftDeleted($salida);
        $this->assertEquals(0, $this->stock()->salidas);
        $this->assertEquals(10, $this->stock()->stock);
    }

    public function test_no_permite_anular_un_ingreso_cuya_mercancia_ya_salio(): void
    {
        $ingreso = $this->registrar('INGRESO', 10);
        $this->registrar('SALIDA', 8);

        $this->delete(route('gestion.stock-bodegas.movimientos.destroy', [$this->stock(), $ingreso]))->assertRedirect();

        $this->assertNotSoftDeleted($ingreso);
        $this->assertEquals(2, $this->stock()->stock);
    }

    public function test_los_movimientos_de_otro_documento_no_se_corrigen_ni_se_anulan_aqui(): void
    {
        $this->registrar('INGRESO', 10);
        $deUnPedido = $this->stock()->detalles()->forceCreate([
            'producto_id' => $this->producto->id,
            'user_id' => $this->usuario->id,
            'tipo' => 'SALIDA',
            'cantidad' => 2,
            'tenant_id' => $this->tenant->id,
            'stock_bodega_id' => $this->stock()->id,
            'origen_type' => 'stock_inicial',
            'origen_id' => 99,
            'descripcion' => 'Pedido 15',
        ]);

        $this->put(route('gestion.stock-bodegas.movimientos.update', [$this->stock(), $deUnPedido]), [
            'tipo' => 'SALIDA',
            'cantidad' => 1,
            'descripcion' => 'x',
        ])->assertForbidden();
        $this->delete(route('gestion.stock-bodegas.movimientos.destroy', [$this->stock(), $deUnPedido]))->assertForbidden();

        $this->assertNotSoftDeleted($deUnPedido);
    }

    public function test_un_movimiento_solo_se_toca_desde_su_propio_stock(): void
    {
        $ingreso = $this->registrar('INGRESO', 10);
        $otraBodega = $this->crearEn(Bodega::class, $this->tenant, ['nombre_bodega' => 'Secundaria']);
        app(MoverStock::class)->registrar($this->producto->id, $otraBodega->id, $this->usuario->id, 'INGRESO', 1.0, 'Otro');
        $otroStock = StockBodega::query()->where('bodega_id', $otraBodega->id)->firstOrFail();

        $this->delete(route('gestion.stock-bodegas.movimientos.destroy', [$otroStock, $ingreso]))->assertNotFound();
    }

    public function test_no_toca_existencias_de_otro_tenant(): void
    {
        $ajeno = Tenant::factory()->create();
        $catalogosAjenos = $this->catalogosBasicosDe($ajeno);
        $stockAjeno = $this->crearEn(StockBodega::class, $ajeno, [
            'bodega_id' => $catalogosAjenos['bodega']->id,
            'producto_id' => $this->crearProductoEn($ajeno, $catalogosAjenos)->id,
            'entradas' => 5,
            'salidas' => 0,
        ]);

        $this->get(route('gestion.stock-bodegas.show', $stockAjeno))->assertNotFound();
    }

    public function test_exige_permiso_por_accion(): void
    {
        $ingreso = $this->registrar('INGRESO', 10);

        $this->actingAs($this->personalConPermisos($this->tenant, ['stock_bodegas.ver']));

        $this->get(route('gestion.stock-bodegas.index'))->assertOk();
        $this->get(route('gestion.stock-bodegas.show', $this->stock()))->assertOk();
        $this->post(route('gestion.stock-bodegas.store'), $this->movimiento('INGRESO', 1))->assertForbidden();
        $this->put(route('gestion.stock-bodegas.movimientos.update', [$this->stock(), $ingreso]), [
            'tipo' => 'INGRESO',
            'cantidad' => 1,
            'descripcion' => 'x',
        ])->assertForbidden();
        $this->delete(route('gestion.stock-bodegas.movimientos.destroy', [$this->stock(), $ingreso]))->assertForbidden();

        $this->actingAs($this->personalConPermisos($this->tenant, ['medidas.ver']));
        $this->get(route('gestion.stock-bodegas.index'))->assertForbidden();
    }
}
