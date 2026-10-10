<?php

namespace Tests\Feature\Gestion;

use App\Actions\Stock\MoverStock;
use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\DetalleStock;
use App\Models\Importacion;
use App\Models\Medida;
use App\Models\Producto;
use App\Models\StockBodega;
use App\Models\StockInicial;
use App\Models\SubCategoria;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportarExportarStockTest extends TestCase
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

        Storage::fake('local');
        $this->prepararGestion();
        $this->usuario = $this->administradorDe($this->tenant);
        $this->actingAs($this->usuario);

        $this->catalogos = $this->catalogosBasicosDe($this->tenant);
        $this->producto = $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'MP-001']);
    }

    /**
     * Sube el archivo, relaciona las columnas (las sugeridas si no se indican) y confirma.
     *
     * @param  array<string, int|null>|null  $mapeo
     */
    private function importar(string $modulo, string $contenido, string $modo = 'crear', ?array $mapeo = null): Importacion
    {
        $this->post(route('gestion.importar.store', $modulo), [
            'archivo' => UploadedFile::fake()->createWithContent('stock.csv', $contenido),
        ])->assertSessionHasNoErrors();

        $importacion = Importacion::latest('id')->firstOrFail();
        $pantalla = $modulo === 'stock_inicial' ? 'gestion.stock-inicial.index' : 'gestion.stock-bodegas.index';
        $sugerencia = $this->get(route($pantalla))->viewData('page')['props']['importacionPendiente']['sugerencia'];

        $this->post(route('gestion.importar.confirmar', $importacion), [
            'mapeo' => $mapeo ?? $sugerencia,
            'modo' => $modo,
        ])->assertSessionHasNoErrors();

        return $importacion->refresh();
    }

    private function stock(?Producto $producto = null): StockBodega
    {
        return StockBodega::query()->where('producto_id', ($producto ?? $this->producto)->id)->firstOrFail();
    }

    private function salida(float $cantidad): void
    {
        app(MoverStock::class)->registrar($this->producto->id, $this->catalogos['bodega']->id, $this->usuario->id, 'SALIDA', $cantidad, 'Venta');
    }

    // --- Stock inicial -------------------------------------------------------

    public function test_importa_el_stock_inicial_por_codigo_y_bodega_y_genera_sus_movimientos(): void
    {
        $segundo = $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'PT-001', 'nombre_producto' => 'Serrucho']);

        $importacion = $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nMP-001;principal;120\nPT-001;Principal;35,5\n");

        $this->assertSame(Importacion::COMPLETADA, $importacion->estado);
        $this->assertSame(2, $importacion->filas_creadas);
        $this->assertSame(2, StockInicial::count());
        $this->assertEquals(120, $this->stock()->stock);
        $this->assertEquals(35.5, $this->stock($segundo)->stock);

        $movimiento = DetalleStock::where('producto_id', $this->producto->id)->firstOrFail();
        $this->assertSame('INGRESO', $movimiento->tipo);
        $this->assertSame('stock_inicial', $movimiento->origen_type);
        $this->assertSame('Stock inicial', $movimiento->descripcion);
        $this->assertSame($this->usuario->id, $movimiento->user_id);
    }

    public function test_sugiere_las_columnas_del_stock_inicial_por_nombres_comunes(): void
    {
        $this->post(route('gestion.importar.store', 'stock_inicial'), [
            'archivo' => UploadedFile::fake()->createWithContent('s.csv', "SKU;Almacén;Unidades;Otra\nMP-001;Principal;5;x\n"),
        ]);

        $this->get(route('gestion.stock-inicial.index'))
            ->assertInertia(fn ($page) => $page
                ->where('importacionPendiente.sugerencia.producto', 0)
                ->where('importacionPendiente.sugerencia.bodega', 1)
                ->where('importacionPendiente.sugerencia.cantidad', 2)
                ->where('importacionPendiente.modos', ['crear', 'actualizar', 'ambos'])
                ->where('importacionPendiente.identificador', 'su producto y su bodega'));
    }

    public function test_crear_omite_el_que_ya_existe_y_rechaza_el_eliminado(): void
    {
        $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nMP-001;Principal;10\n");

        $repetida = $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nMP-001;Principal;99\n");
        $this->assertSame(1, $repetida->filas_omitidas);
        $this->assertEquals(10, $this->stock()->stock);

        StockInicial::firstOrFail()->delete();
        $eliminado = $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nMP-001;Principal;99\n");
        $this->assertSame(1, $eliminado->filas_con_error);
        $this->assertStringContainsString('Restáuralo', $eliminado->errores[0]['mensaje']);
    }

    public function test_actualizar_cambia_la_cantidad_y_corrige_el_movimiento(): void
    {
        $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nMP-001;Principal;100\n");

        $importacion = $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nMP-001;Principal;80\n", 'actualizar');

        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertEquals(80, StockInicial::firstOrFail()->cantidad);
        $this->assertEquals(80, $this->stock()->stock);
        $this->assertSame(1, DetalleStock::count());
        $this->assertStringContainsString('corregido de 100 a 80', DetalleStock::firstOrFail()->descripcion);
    }

    public function test_actualizar_sin_cambios_o_sin_stock_previo_se_reporta(): void
    {
        $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nMP-001;Principal;100\n");
        $otro = $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'PT-001']);

        $importacion = $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nMP-001;Principal;100\nPT-001;Principal;5\n", 'actualizar');

        $this->assertSame(1, $importacion->filas_omitidas);
        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertStringContainsString('No hay un stock inicial', $importacion->errores[0]['mensaje']);
        $this->assertSame(0, StockBodega::where('producto_id', $otro->id)->count());
    }

    public function test_no_se_puede_reducir_el_stock_inicial_por_debajo_de_lo_que_ya_salio(): void
    {
        $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nMP-001;Principal;100\n");
        $this->salida(70);

        $importacion = $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nMP-001;Principal;50\n", 'actualizar');

        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertStringContainsString('stock quedaría en negativo', $importacion->errores[0]['mensaje']);
        $this->assertEquals(100, StockInicial::firstOrFail()->cantidad);
        $this->assertEquals(30, $this->stock()->stock);
    }

    public function test_crear_y_actualizar_en_el_mismo_archivo(): void
    {
        $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nMP-001;Principal;10\n");
        $nuevo = $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'PT-001']);

        $importacion = $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nMP-001;Principal;15\nPT-001;Principal;7\n", 'ambos');

        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertSame(1, $importacion->filas_creadas);
        $this->assertEquals(15, $this->stock()->stock);
        $this->assertEquals(7, $this->stock($nuevo)->stock);
    }

    public function test_reporta_productos_bodegas_y_cantidades_invalidas(): void
    {
        $importacion = $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nXX-999;Principal;5\nMP-001;Fantasma;5\nMP-001;Principal;abc\nMP-001;Principal;-3\nMP-001;Principal;8\n");

        $this->assertSame(4, $importacion->filas_con_error);
        $this->assertSame(1, $importacion->filas_creadas);
        $this->assertSame([2, 3, 4, 5], array_column($importacion->errores, 'fila'));
        $this->assertStringContainsString('No existe un producto con código XX-999', $importacion->errores[0]['mensaje']);
        $this->assertStringContainsString('La bodega Fantasma no existe', $importacion->errores[1]['mensaje']);
        $this->assertEquals(8, $this->stock()->stock);
    }

    public function test_no_usa_productos_ni_bodegas_de_otro_tenant(): void
    {
        $ajeno = Tenant::factory()->create();
        $catalogosAjenos = $this->catalogosBasicosDe($ajeno);
        $this->crearProductoEn($ajeno, $catalogosAjenos, ['codigo_producto' => 'AJ-001']);

        $importacion = $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nAJ-001;Principal;5\n");

        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertSame(0, StockInicial::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());
    }

    public function test_exporta_el_stock_inicial_y_se_puede_editar_y_reimportar(): void
    {
        $this->importar('stock_inicial', "Código del producto;Bodega;Cantidad\nMP-001;Principal;100\n");

        $csv = $this->get(route('gestion.exportar', ['modulo' => 'stock_inicial', 'formato' => 'csv']));
        $csv->assertOk()->assertDownload();
        $contenido = $csv->streamedContent();
        $this->assertStringContainsString('MP-001;Martillo;Principal;100', $contenido);

        $importacion = $this->importar('stock_inicial', str_replace('Principal;100', 'Principal;60', $contenido), 'actualizar');

        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertEquals(60, $this->stock()->stock);
    }

    public function test_importar_stock_inicial_exige_permisos_de_crear_y_editar(): void
    {
        $this->post(route('gestion.importar.store', 'stock_inicial'), [
            'archivo' => UploadedFile::fake()->createWithContent('s.csv', "Código del producto;Bodega;Cantidad\nMP-001;Principal;5\n"),
        ]);
        $pendiente = Importacion::firstOrFail();

        $this->actingAs($this->personalConPermisos($this->tenant, ['stock_inicial.ver', 'stock_inicial.crear']));
        $mapeo = ['producto' => 0, 'bodega' => 1, 'cantidad' => 2];

        $this->post(route('gestion.importar.confirmar', $pendiente), ['mapeo' => $mapeo, 'modo' => 'actualizar'])->assertForbidden();
        $this->post(route('gestion.importar.confirmar', $pendiente), ['mapeo' => $mapeo, 'modo' => 'crear'])->assertSessionHasNoErrors();

        $this->actingAs($this->personalConPermisos($this->tenant, ['productos.ver']));
        $this->get(route('gestion.exportar', ['modulo' => 'stock_inicial', 'formato' => 'csv']))->assertForbidden();
    }

    // --- Movimientos de inventario -------------------------------------------

    public function test_importa_ingresos_y_salidas_como_movimientos_manuales(): void
    {
        $importacion = $this->importar(
            'movimientos_stock',
            "Código del producto;Bodega;Tipo;Cantidad;Motivo\nMP-001;Principal;Ingreso;50;Compra\nMP-001;Principal;Entrada;10,5;\nMP-001;Principal;Salida;5,5;Merma\n",
        );

        $this->assertSame(Importacion::COMPLETADA, $importacion->estado);
        $this->assertSame(3, $importacion->filas_creadas);
        $this->assertEquals(60.5, $this->stock()->entradas);
        $this->assertEquals(5.5, $this->stock()->salidas);
        $this->assertEquals(55, $this->stock()->stock);

        $this->assertSame(3, DetalleStock::count());
        $this->assertTrue(DetalleStock::get()->every(fn (DetalleStock $m): bool => $m->esManual()));
        $this->assertDatabaseHas('detalle_stocks', ['descripcion' => 'Merma', 'tipo' => 'SALIDA']);
        $this->assertDatabaseHas('detalle_stocks', ['descripcion' => 'Importación de movimientos', 'tipo' => 'INGRESO']);
    }

    public function test_una_salida_que_supera_el_stock_se_rechaza_y_las_demas_filas_siguen(): void
    {
        $importacion = $this->importar(
            'movimientos_stock',
            "Código del producto;Bodega;Tipo;Cantidad\nMP-001;Principal;Ingreso;10\nMP-001;Principal;Salida;50\nMP-001;Principal;Salida;4\n",
        );

        $this->assertSame(2, $importacion->filas_creadas);
        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertSame(3, $importacion->errores[0]['fila']);
        $this->assertStringContainsString('supera lo que hay en la bodega', $importacion->errores[0]['mensaje']);
        $this->assertEquals(6, $this->stock()->stock);
    }

    public function test_rechaza_tipos_cantidades_y_productos_invalidos(): void
    {
        $importacion = $this->importar(
            'movimientos_stock',
            "Código del producto;Bodega;Tipo;Cantidad\nMP-001;Principal;Traspaso;5\nMP-001;Principal;Ingreso;0\nXX-999;Principal;Ingreso;5\nMP-001;Fantasma;Ingreso;5\n",
        );

        $this->assertSame(4, $importacion->filas_con_error);
        $this->assertSame(0, DetalleStock::count());
    }

    public function test_los_movimientos_solo_admiten_el_modo_crear(): void
    {
        $this->post(route('gestion.importar.store', 'movimientos_stock'), [
            'archivo' => UploadedFile::fake()->createWithContent('m.csv', "Código del producto;Bodega;Tipo;Cantidad\nMP-001;Principal;Ingreso;5\n"),
        ]);
        $pendiente = Importacion::firstOrFail();

        $this->get(route('gestion.stock-bodegas.index'))
            ->assertInertia(fn ($page) => $page
                ->where('importacionPendiente.modos', ['crear'])
                ->where('importacionPendiente.etiqueta', 'Movimientos de inventario')
                ->whereType('importacionPendiente.aviso', 'string'));

        $mapeo = ['producto' => 0, 'bodega' => 1, 'tipo' => 2, 'cantidad' => 3];
        $this->post(route('gestion.importar.confirmar', $pendiente), ['mapeo' => $mapeo, 'modo' => 'actualizar'])->assertSessionHasErrors('modo');
        $this->post(route('gestion.importar.confirmar', $pendiente), ['mapeo' => $mapeo, 'modo' => 'crear'])->assertSessionHasNoErrors();
    }

    public function test_importar_movimientos_exige_el_permiso_de_crear_en_stock_por_bodega(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['stock_bodegas.ver']));

        $this->post(route('gestion.importar.store', 'movimientos_stock'), [
            'archivo' => UploadedFile::fake()->createWithContent('m.csv', "Código del producto\nMP-001\n"),
        ])->assertForbidden();
    }

    public function test_exporta_las_existencias_por_bodega(): void
    {
        app(MoverStock::class)->registrar($this->producto->id, $this->catalogos['bodega']->id, $this->usuario->id, 'INGRESO', 20.0, 'Compra');
        $this->salida(5);

        $ajeno = Tenant::factory()->create();
        $catalogosAjenos = $this->catalogosBasicosDe($ajeno);
        $this->crearEn(StockBodega::class, $ajeno, [
            'bodega_id' => $catalogosAjenos['bodega']->id,
            'producto_id' => $this->crearProductoEn($ajeno, $catalogosAjenos, ['codigo_producto' => 'AJ-001'])->id,
            'entradas' => 9,
            'salidas' => 0,
        ]);

        $csv = $this->get(route('gestion.exportar', ['modulo' => 'stock_bodegas', 'formato' => 'csv']));
        $csv->assertOk()->assertDownload();
        $contenido = $csv->streamedContent();
        $this->assertStringContainsString('MP-001;Martillo;Principal;Unidad;20.00;5.00;15.00', $contenido);
        $this->assertStringNotContainsString('AJ-001', $contenido);

        $this->get(route('gestion.exportar', ['modulo' => 'stock_bodegas', 'formato' => 'xlsx']))->assertOk();
    }
}
