<?php

namespace Tests\Feature\Gestion;

use App\Actions\Stock\MoverStock;
use App\Models\Bodega;
use App\Models\DetalleStock;
use App\Models\Producto;
use App\Models\StockBodega;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReconciliarStockTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    private User $usuario;

    private Producto $producto;

    private Bodega $bodega;

    /** @var array<string, IlluminateDatabaseEloquentModel> */
    private array $catalogos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararGestion();
        $this->usuario = $this->administradorDe($this->tenant);
        $this->actingAs($this->usuario);

        $this->catalogos = $this->catalogosBasicosDe($this->tenant);
        $this->producto = $this->crearProductoEn($this->tenant, $this->catalogos);
        $this->bodega = $this->catalogos['bodega'];
    }

    private function mover(string $tipo, float $cantidad): DetalleStock
    {
        return app(MoverStock::class)->registrar($this->producto->id, $this->bodega->id, $this->usuario->id, $tipo, $cantidad, 'Prueba');
    }

    private function stock(): StockBodega
    {
        return StockBodega::query()->where('producto_id', $this->producto->id)->firstOrFail();
    }

    /**
     * Descuadra las existencias saltándose el modelo, como lo haría un cambio manual en la base.
     */
    private function descuadrar(): void
    {
        DB::table('stock_bodegas')->update(['entradas' => 999, 'salidas' => 1, 'stock' => 998]);
    }

    public function test_avisa_que_todo_cuadra_cuando_las_existencias_coinciden_con_los_movimientos(): void
    {
        $this->mover('INGRESO', 10);
        $this->mover('SALIDA', 4);

        $this->artisan('stock:reconciliar')
            ->expectsOutputToContain('Todo cuadra')
            ->assertExitCode(0);
    }

    public function test_los_movimientos_anulados_no_cuentan(): void
    {
        $this->mover('INGRESO', 10);
        $salida = $this->mover('SALIDA', 4);
        app(MoverStock::class)->anular($salida);

        $this->artisan('stock:reconciliar')->assertExitCode(0);
        $this->assertEquals(10, $this->stock()->stock);
    }

    public function test_detecta_existencias_que_no_coinciden_con_sus_movimientos(): void
    {
        $this->mover('INGRESO', 10);
        $this->descuadrar();

        $this->artisan('stock:reconciliar')
            ->expectsOutputToContain('no coinciden con sus movimientos')
            ->assertExitCode(1);

        // Solo revisa: no cambia nada sin --corregir.
        $this->assertEquals(999, $this->stock()->entradas);
    }

    public function test_corregir_deja_las_existencias_como_la_suma_de_sus_movimientos(): void
    {
        $this->mover('INGRESO', 10);
        $this->mover('SALIDA', 4);
        $this->descuadrar();

        $this->artisan('stock:reconciliar', ['--corregir' => true])
            ->expectsOutputToContain('Se corrigieron 1 existencia(s)')
            ->assertExitCode(0);

        $this->assertEquals(10, $this->stock()->entradas);
        $this->assertEquals(4, $this->stock()->salidas);
        $this->assertEquals(6, $this->stock()->stock);

        $this->artisan('stock:reconciliar')->assertExitCode(0);
    }

    public function test_detecta_un_stock_que_no_es_entradas_menos_salidas(): void
    {
        $this->mover('INGRESO', 10);
        DB::table('stock_bodegas')->update(['stock' => 3]);

        $this->artisan('stock:reconciliar')->assertExitCode(1);
        $this->artisan('stock:reconciliar', ['--corregir' => true])->assertExitCode(0);

        $this->assertEquals(10, $this->stock()->stock);
    }

    public function test_detecta_existencias_que_se_movieron_sin_dejar_movimientos(): void
    {
        $this->crearEn(StockBodega::class, $this->tenant, [
            'bodega_id' => $this->bodega->id,
            'producto_id' => $this->producto->id,
            'entradas' => 7,
            'salidas' => 0,
        ]);

        $this->artisan('stock:reconciliar')->assertExitCode(1);
        $this->artisan('stock:reconciliar', ['--corregir' => true])->assertExitCode(0);

        $this->assertEquals(0, $this->stock()->stock);
    }

    public function test_la_opcion_tenant_limita_la_revision_a_una_empresa(): void
    {
        $this->mover('INGRESO', 10);

        $ajeno = Tenant::factory()->create();
        $catalogosAjenos = $this->catalogosBasicosDe($ajeno);
        $productoAjeno = $this->crearProductoEn($ajeno, $catalogosAjenos);
        $stockAjeno = $this->crearEn(StockBodega::class, $ajeno, [
            'bodega_id' => $catalogosAjenos['bodega']->id,
            'producto_id' => $productoAjeno->id,
            'entradas' => 50,
            'salidas' => 0,
        ]);

        // La empresa propia cuadra; la ajena no.
        $this->artisan('stock:reconciliar', ['--tenant' => $this->tenant->id])->assertExitCode(0);
        $this->artisan('stock:reconciliar', ['--tenant' => $ajeno->id])->assertExitCode(1);

        // Corregir solo una empresa no toca la otra.
        $this->descuadrar();
        $this->artisan('stock:reconciliar', ['--tenant' => $this->tenant->id, '--corregir' => true])->assertExitCode(0);
        $this->assertEquals(10, $this->stock()->entradas);
        $this->assertEquals(999, StockBodega::withoutGlobalScopes()->findOrFail($stockAjeno->id)->entradas);
    }

    public function test_avisa_de_movimientos_de_otro_producto_y_no_los_corrige_solo(): void
    {
        $movimiento = $this->mover('INGRESO', 10);
        $otro = $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'P-002', 'nombre_producto' => 'Serrucho']);
        DB::table('detalle_stocks')->where('id', $movimiento->id)->update(['producto_id' => $otro->id]);

        $this->artisan('stock:reconciliar', ['--corregir' => true])
            ->expectsOutputToContain('pertenecen a otro producto')
            ->assertExitCode(1);
    }
}
