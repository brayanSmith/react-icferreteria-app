<?php

namespace Tests\Feature\Gestion;

use App\Models\DetalleStock;
use App\Models\StockBodega;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetalleStockTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararGestion();
        $this->actingAs($this->administradorDe($this->tenant));
    }

    private function crearMovimiento(Tenant $tenant, array $atributos = []): DetalleStock
    {
        $catalogos = $this->catalogosBasicosDe($tenant);
        $producto = $this->crearProductoEn($tenant, $catalogos);
        $stock = $this->crearEn(StockBodega::class, $tenant, [
            'bodega_id' => $catalogos['bodega']->id,
            'producto_id' => $producto->id,
            'entradas' => 10,
            'salidas' => 0,
        ]);

        return $this->crearEn(DetalleStock::class, $tenant, [
            'stock_bodega_id' => $stock->id,
            'producto_id' => $producto->id,
            'user_id' => User::factory()->create(['tenant_id' => $tenant->id])->id,
            'tipo' => DetalleStock::INGRESO,
            'cantidad' => 10,
            'descripcion' => 'Stock inicial',
            ...$atributos,
        ]);
    }

    public function test_el_movimiento_se_relaciona_con_su_stock_producto_y_usuario(): void
    {
        $movimiento = $this->crearMovimiento($this->tenant);

        $this->assertTrue($movimiento->stockBodega->is(StockBodega::firstOrFail()));
        $this->assertSame('Martillo', $movimiento->producto->nombre_producto);
        $this->assertTrue($movimiento->usuario->is(User::findOrFail($movimiento->user_id)));
        $this->assertEquals(10, $movimiento->cantidad);
        $this->assertTrue($movimiento->esIngreso());
    }

    public function test_distingue_ingresos_de_salidas(): void
    {
        $this->assertFalse($this->crearMovimiento($this->tenant, ['tipo' => DetalleStock::SALIDA])->esIngreso());
    }

    public function test_se_elimina_de_forma_logica_y_queda_aislado_por_tenant(): void
    {
        $propio = $this->crearMovimiento($this->tenant);
        $this->crearMovimiento(Tenant::factory()->create());

        $this->assertSame(1, DetalleStock::count());

        $propio->delete();

        $this->assertSoftDeleted($propio);
        $this->assertSame(0, DetalleStock::count());
    }
}
