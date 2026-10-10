<?php

namespace Tests\Feature\Gestion;

use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\Importacion;
use App\Models\Medida;
use App\Models\Producto;
use App\Models\SubCategoria;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportarExportarProductosTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    /** @var array{medida: Medida, bodega: Bodega, categoria: Categoria, subcategoria: SubCategoria} */
    private array $catalogos;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->prepararGestion();
        $this->actingAs($this->administradorDe($this->tenant));
        $this->catalogos = $this->catalogosBasicosDe($this->tenant);
    }

    /**
     * Sube el archivo, relaciona las columnas (las sugeridas si no se indican) y confirma.
     *
     * @param  array<string, int|null>|null  $mapeo
     */
    private function importar(string $contenido, string $modo = 'crear', ?array $mapeo = null): Importacion
    {
        $this->post(route('gestion.importar.store', 'productos'), [
            'archivo' => UploadedFile::fake()->createWithContent('productos.csv', $contenido),
        ])->assertSessionHasNoErrors();

        $importacion = Importacion::latest('id')->firstOrFail();
        $sugerencia = $this->get(route('gestion.productos.index'))->viewData('page')['props']['importacionPendiente']['sugerencia'];

        $this->post(route('gestion.importar.confirmar', $importacion), [
            'mapeo' => $mapeo ?? $sugerencia,
            'modo' => $modo,
        ])->assertSessionHasNoErrors();

        return $importacion->refresh();
    }

    private function producto(string $codigo, array $atributos = []): Producto
    {
        return $this->crearProductoEn($this->tenant, $this->catalogos, [
            'codigo_producto' => $codigo,
            'nombre_producto' => 'Producto '.$codigo,
            'concatenar_codigo_nombre' => "{$codigo} - Producto {$codigo}",
            ...$atributos,
        ]);
    }

    public function test_crea_productos_con_codigo_automatico_y_relaciona_los_catalogos_por_nombre(): void
    {
        $importacion = $this->importar(
            "Nombre;Tipo de producto;Categoría;Subcategoría;Medida;Costo;Precio detal;Activo;Marca\n"
            ."Martillo;Producto terminado;Herramientas;Manuales;Unidad;12.000,50;18000;Sí;Truper\n"
            ."Pintura;Materia prima;herramientas;manuales;unidad;45000;;No;\n"
            ."Cinta;;Herramientas;Manuales;Unidad;1000;1500;;\n",
        );

        $this->assertSame(Importacion::COMPLETADA, $importacion->estado);
        $this->assertSame(3, $importacion->filas_creadas);

        $martillo = Producto::where('nombre_producto', 'Martillo')->firstOrFail();
        $this->assertSame('PT-001', $martillo->codigo_producto);
        $this->assertSame('PT-001 - Martillo', $martillo->concatenar_codigo_nombre);
        $this->assertEquals(12000.5, $martillo->costo_producto);
        $this->assertTrue((bool) $martillo->activo);
        $this->assertSame($this->catalogos['medida']->id, $martillo->medida_id);
        $this->assertSame($this->catalogos['subcategoria']->id, $martillo->sub_categoria_id);
        $this->assertSame($this->tenant->id, $martillo->tenant_id);

        $pintura = Producto::where('nombre_producto', 'Pintura')->firstOrFail();
        $this->assertSame('MP-001', $pintura->codigo_producto);
        $this->assertFalse((bool) $pintura->activo);

        // Sin tipo de producto, queda como producto terminado y toma el siguiente consecutivo.
        $this->assertSame('PT-002', Producto::where('nombre_producto', 'Cinta')->firstOrFail()->codigo_producto);
    }

    public function test_ignora_el_codigo_del_archivo_al_crear(): void
    {
        $this->importar("Código;Nombre;Categoría;Subcategoría;Medida\nXYZ-9;Martillo;Herramientas;Manuales;Unidad\n");

        $this->assertDatabaseHas('productos', ['nombre_producto' => 'Martillo', 'codigo_producto' => 'PT-001']);
        $this->assertDatabaseMissing('productos', ['codigo_producto' => 'XYZ-9']);
    }

    public function test_importar_dos_veces_no_duplica_por_nombre_y_marca(): void
    {
        $contenido = "Nombre;Categoría;Subcategoría;Medida;Marca\nMartillo;Herramientas;Manuales;Unidad;Truper\nMartillo;Herramientas;Manuales;Unidad;Stanley\n";

        $this->importar($contenido);
        $segunda = $this->importar($contenido);

        $this->assertSame(2, Producto::count());
        $this->assertSame(0, $segunda->filas_creadas);
        $this->assertSame(2, $segunda->filas_omitidas);
    }

    public function test_rechaza_las_filas_con_catalogos_inexistentes_o_datos_invalidos(): void
    {
        $otra = $this->catalogos['categoria']->replicate()->forceFill(['tenant_id' => $this->tenant->id, 'nombre_categoria' => 'Pinturas']);
        $otra->save();

        $importacion = $this->importar(
            "Nombre;Categoría;Subcategoría;Medida;Costo;Tipo de producto\n"
            ."Buena;Herramientas;Manuales;Unidad;100;\n"
            ."SinCategoria;Fantasma;Manuales;Unidad;100;\n"
            ."SubAjena;Pinturas;Manuales;Unidad;100;\n"
            ."SinMedida;Herramientas;Manuales;Galón;100;\n"
            ."Negativo;Herramientas;Manuales;Unidad;-5;\n"
            ."TipoMalo;Herramientas;Manuales;Unidad;100;Inventado\n"
            .";Herramientas;Manuales;Unidad;100;\n",
        );

        $this->assertSame(1, $importacion->filas_creadas);
        $this->assertSame(6, $importacion->filas_con_error);
        $this->assertSame([3, 4, 5, 6, 7, 8], array_column($importacion->errores, 'fila'));
        $this->assertStringContainsString('La categoría Fantasma no existe', $importacion->errores[0]['mensaje']);
        $this->assertStringContainsString('subcategoría Manuales no existe en esa categoría', $importacion->errores[1]['mensaje']);
        $this->assertStringContainsString('La medida Galón no existe', $importacion->errores[2]['mensaje']);
        $this->assertStringContainsString('Costo', $importacion->errores[3]['mensaje']);
        $this->assertStringContainsString('Falta Nombre', $importacion->errores[5]['mensaje']);
        $this->assertSame(1, Producto::count());
    }

    public function test_no_usa_los_catalogos_de_otro_tenant(): void
    {
        $ajeno = $this->catalogosBasicosDe(Tenant::factory()->create());
        $ajeno['medida']->forceFill(['nombre_medida' => 'Solo ajena'])->save();

        $importacion = $this->importar("Nombre;Categoría;Subcategoría;Medida\nMartillo;Herramientas;Manuales;Solo ajena\n");

        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertSame(0, Producto::count());
    }

    public function test_relaciona_columnas_con_nombres_que_el_sistema_no_conoce(): void
    {
        $this->importar(
            "Articulo raro;Rubro;Familia;UM;Pvp\nMartillo;Herramientas;Manuales;Unidad;18000\n",
            mapeo: [
                'nombre_producto' => 0, 'categoria' => 1, 'subcategoria' => 2, 'medida' => 3, 'valor_detal_producto' => 4,
            ],
        );

        $this->assertDatabaseHas('productos', ['nombre_producto' => 'Martillo', 'valor_detal_producto' => '18000.00']);
    }

    public function test_exige_las_columnas_obligatorias_segun_el_modo(): void
    {
        $this->post(route('gestion.importar.store', 'productos'), [
            'archivo' => UploadedFile::fake()->createWithContent('p.csv', "Nombre;Costo\nMartillo;100\n"),
        ]);
        $importacion = Importacion::firstOrFail();

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['nombre_producto' => 0], 'modo' => 'crear'])
            ->assertSessionHasErrors(['mapeo.categoria', 'mapeo.subcategoria', 'mapeo.medida']);

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['nombre_producto' => 0], 'modo' => 'actualizar'])
            ->assertSessionHasErrors('mapeo.codigo_producto');
    }

    public function test_actualizar_cambia_solo_lo_que_la_fila_trae_y_conserva_el_codigo(): void
    {
        $producto = $this->producto('PT-001', ['marca' => 'Truper', 'costo_producto' => 10, 'valor_detal_producto' => 20]);

        $importacion = $this->importar("Código;Nombre;Costo;Precio detal;Marca\nPT-001;Martillo nuevo;15;;\n", 'actualizar');

        $this->assertSame(1, $importacion->filas_actualizadas);
        $producto->refresh();
        $this->assertSame('PT-001', $producto->codigo_producto);
        $this->assertSame('Martillo nuevo', $producto->nombre_producto);
        $this->assertSame('PT-001 - Martillo nuevo', $producto->concatenar_codigo_nombre);
        $this->assertEquals(15, $producto->costo_producto);
        $this->assertEquals(20, $producto->valor_detal_producto, 'Una celda vacía no borra el dato.');
        $this->assertSame('Truper', $producto->marca);
    }

    public function test_actualizar_cambia_catalogos_por_nombre_y_exige_subcategoria_al_cambiar_de_categoria(): void
    {
        $producto = $this->producto('PT-001');
        $pinturas = $this->crearEn(Categoria::class, $this->tenant, ['nombre_categoria' => 'Pinturas']);
        $pinturas->subCategorias()->create(['nombre_sub_categoria' => 'Esmaltes']);

        $importacion = $this->importar(
            "Código;Categoría;Subcategoría\nPT-001;Pinturas;\nPT-001;Pinturas;Esmaltes\n",
            'actualizar',
        );

        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertStringContainsString('indica también la subcategoría', $importacion->errores[0]['mensaje']);
        $this->assertSame($pinturas->id, $producto->refresh()->categoria_id);
    }

    public function test_actualizar_rechaza_codigos_inexistentes_o_ajenos_y_omite_lo_igual(): void
    {
        $this->producto('PT-001', ['costo_producto' => 10]);
        $this->crearProductoEn(Tenant::factory()->create(), $this->catalogosBasicosDe(Tenant::factory()->create()), ['codigo_producto' => 'PT-050']);

        $importacion = $this->importar("Código;Costo\nPT-001;10\nPT-999;5\n", 'actualizar');

        $this->assertSame(1, $importacion->filas_omitidas, 'Un 10 igual a 10.00 no es un cambio.');
        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertStringContainsString('No existe un producto con el código PT-999', $importacion->errores[0]['mensaje']);
    }

    public function test_un_mismo_archivo_puede_crear_y_actualizar(): void
    {
        $existente = $this->producto('PT-001');

        $importacion = $this->importar(
            "Código;Nombre;Categoría;Subcategoría;Medida\nPT-001;Renombrado;;;\n;Nuevo;Herramientas;Manuales;Unidad\nPT-404;Fantasma;;;\n",
            'ambos',
        );

        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertSame(1, $importacion->filas_creadas);
        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertSame('Renombrado', $existente->refresh()->nombre_producto);
        $this->assertDatabaseHas('productos', ['nombre_producto' => 'Nuevo', 'codigo_producto' => 'PT-002']);
    }

    public function test_exporta_los_productos_del_tenant_con_nombres_legibles(): void
    {
        $this->producto('MP-001', ['categoria_producto' => 'MATERIA_PRIMA', 'marca' => 'Truper', 'activo' => false, 'volumen_producto' => 'EXTRA_PEQUEÑO']);
        $this->crearProductoEn(Tenant::factory()->create(), $this->catalogosBasicosDe(Tenant::factory()->create()), ['codigo_producto' => 'AJENO-1']);

        $respuesta = $this->get(route('gestion.exportar', ['modulo' => 'productos', 'formato' => 'csv']));
        $respuesta->assertOk()->assertDownload();
        $contenido = $respuesta->streamedContent();

        $this->assertStringContainsString('Código;"Tipo de producto";Nombre', $contenido);
        $this->assertStringContainsString('MP-001;"Materia prima";"Producto MP-001"', $contenido);
        $this->assertStringContainsString('Herramientas;Manuales;Unidad', $contenido);
        $this->assertStringContainsString('Truper', $contenido);
        $this->assertStringContainsString('"Extra pequeño"', $contenido);
        $this->assertStringNotContainsString('AJENO-1', $contenido);

        $this->get(route('gestion.exportar', ['modulo' => 'productos', 'formato' => 'xlsx']))->assertOk()->assertDownload();
        $this->get(route('gestion.exportar', ['modulo' => 'productos', 'formato' => 'pdf']))->assertOk()->assertDownload();
    }

    public function test_un_archivo_exportado_se_puede_editar_y_reimportar_para_actualizar(): void
    {
        $producto = $this->producto('MP-001', ['categoria_producto' => 'MATERIA_PRIMA', 'activo' => true]);

        $exportado = $this->get(route('gestion.exportar', ['modulo' => 'productos', 'formato' => 'csv']))->streamedContent();
        $importacion = $this->importar(str_replace('Producto MP-001', 'Editado en Excel', $exportado), 'actualizar');

        $this->assertSame(0, $importacion->filas_con_error);
        $this->assertSame(1, $importacion->filas_actualizadas);
        $producto->refresh();
        $this->assertSame('Editado en Excel', $producto->nombre_producto);
        $this->assertSame('MATERIA_PRIMA', $producto->categoria_producto);
    }

    public function test_importar_exige_crear_y_exportar_exige_ver(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['productos.ver']));

        $this->get(route('gestion.exportar', ['modulo' => 'productos', 'formato' => 'csv']))->assertOk();
        $this->post(route('gestion.importar.store', 'productos'), [
            'archivo' => UploadedFile::fake()->createWithContent('p.csv', "Nombre\nX\n"),
        ])->assertForbidden();

        $this->actingAs($this->personalConPermisos($this->tenant, ['medidas.ver']));
        $this->get(route('gestion.exportar', ['modulo' => 'productos', 'formato' => 'csv']))->assertForbidden();
    }
}
