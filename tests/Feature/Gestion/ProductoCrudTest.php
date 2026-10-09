<?php

namespace Tests\Feature\Gestion;

use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\Medida;
use App\Models\Producto;
use App\Models\SubCategoria;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductoCrudTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    /** @var array{medida: Medida, bodega: Bodega, categoria: Categoria, subcategoria: SubCategoria} */
    private array $catalogos;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->prepararGestion();
        $this->actingAs($this->administradorDe($this->tenant));
        $this->catalogos = $this->catalogosBasicosDe($this->tenant);
    }

    /**
     * Datos del formulario. El código no se envía: lo genera el servidor.
     *
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function datos(array $sobrescribir = []): array
    {
        return [
            'categoria_producto' => 'PRODUCTO_TERMINADO',
            'nombre_producto' => 'Martillo de acero',
            'costo_producto' => '10000',
            'valor_detal_producto' => '15000',
            'medida_id' => $this->catalogos['medida']->id,
            'categoria_id' => $this->catalogos['categoria']->id,
            'sub_categoria_id' => $this->catalogos['subcategoria']->id,
            'activo' => '1',
            ...$sobrescribir,
        ];
    }

    public function test_crea_un_producto_con_numero_consecutivo_y_codigo_automatico(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos())
            ->assertRedirect(route('gestion.productos.index'));

        $producto = Producto::firstOrFail();

        $this->assertSame($this->tenant->id, $producto->tenant_id);
        $this->assertSame(1, $producto->numero);
        $this->assertSame('PT-001', $producto->codigo_producto);
        $this->assertSame('PT-001 - Martillo de acero', $producto->concatenar_codigo_nombre);
        $this->assertEquals(1, $producto->activo);
    }

    public function test_cada_tipo_de_producto_lleva_su_propio_prefijo_y_consecutivo(): void
    {
        $tipos = [
            ['MATERIA_PRIMA', 'MP-001'],
            ['MATERIA_PRIMA', 'MP-002'],
            ['PRODUCTO_TERMINADO', 'PT-001'],
            ['MATERIA_PRIMA', 'MP-003'],
            ['PRODUCTO_TERMINADO', 'PT-002'],
            ['OTRO', 'OT-001'],
            ['DESCUENTO', 'DS-001'],
            ['GASTOS', 'GS-001'],
        ];

        foreach ($tipos as [$tipo, $codigoEsperado]) {
            $this->post(route('gestion.productos.store'), $this->datos(['categoria_producto' => $tipo]))
                ->assertSessionHasNoErrors();

            $this->assertSame($codigoEsperado, Producto::latest('id')->firstOrFail()->codigo_producto);
        }
    }

    public function test_cada_tenant_lleva_su_propio_consecutivo_de_codigos(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos(['categoria_producto' => 'MATERIA_PRIMA']));
        $this->post(route('gestion.productos.store'), $this->datos(['categoria_producto' => 'MATERIA_PRIMA']));

        $otroTenant = Tenant::factory()->create();
        $ajenos = $this->catalogosBasicosDe($otroTenant);
        $this->actingAs($this->administradorDe($otroTenant));

        $this->post(route('gestion.productos.store'), [
            ...$this->datos(['categoria_producto' => 'MATERIA_PRIMA']),
            'medida_id' => $ajenos['medida']->id,
            'categoria_id' => $ajenos['categoria']->id,
            'sub_categoria_id' => $ajenos['subcategoria']->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos', ['tenant_id' => $otroTenant->id, 'codigo_producto' => 'MP-001']);
        $this->assertDatabaseHas('productos', ['tenant_id' => $this->tenant->id, 'codigo_producto' => 'MP-002']);
    }

    public function test_ignora_un_codigo_enviado_por_el_usuario(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos(['codigo_producto' => 'HACK-1']));

        $this->assertSame('PT-001', Producto::firstOrFail()->codigo_producto);
    }

    public function test_salta_los_codigos_que_ya_estan_ocupados(): void
    {
        // Uno escrito a mano antes de que el código fuera automático y otro eliminado.
        $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'PT-001']);
        $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'PT-002'])->delete();

        $this->post(route('gestion.productos.store'), $this->datos())->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos', ['codigo_producto' => 'PT-003']);
    }

    public function test_un_envio_invalido_no_consume_el_consecutivo(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos(['nombre_producto' => '']))
            ->assertSessionHasErrors('nombre_producto');
        $this->post(route('gestion.productos.store'), $this->datos())->assertSessionHasNoErrors();

        $this->assertSame('PT-001', Producto::firstOrFail()->codigo_producto);
    }

    public function test_el_codigo_no_cambia_al_editar_aunque_cambie_el_tipo_o_el_nombre(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos(['categoria_producto' => 'MATERIA_PRIMA']));
        $producto = Producto::firstOrFail();

        $this->put(route('gestion.productos.update', $producto), $this->datos([
            'categoria_producto' => 'PRODUCTO_TERMINADO',
            'nombre_producto' => 'Martillo grande',
            'codigo_producto' => 'OTRO-9',
        ]))->assertSessionHasNoErrors();

        $producto->refresh();
        $this->assertSame('MP-001', $producto->codigo_producto);
        $this->assertSame('PRODUCTO_TERMINADO', $producto->categoria_producto);
        $this->assertSame('MP-001 - Martillo grande', $producto->concatenar_codigo_nombre);
    }

    public function test_los_campos_numericos_vacios_se_guardan_como_cero(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos([
            'costo_producto' => '',
            'valor_detal_producto' => '',
            'alerta_producto' => '',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos', [
            'codigo_producto' => 'PT-001',
            'costo_producto' => 0,
            'valor_detal_producto' => 0,
            'alerta_producto' => 0,
        ]);
    }

    public function test_la_subcategoria_debe_pertenecer_a_la_categoria_elegida(): void
    {
        $otraCategoria = $this->crearEn(Categoria::class, $this->tenant, ['nombre_categoria' => 'Pinturas']);
        $otraSub = $otraCategoria->subCategorias()->create(['nombre_sub_categoria' => 'Esmaltes']);

        $this->post(route('gestion.productos.store'), $this->datos(['sub_categoria_id' => $otraSub->id]))
            ->assertSessionHasErrors('sub_categoria_id');

        $this->post(route('gestion.productos.store'), $this->datos(['categoria_id' => $otraCategoria->id, 'sub_categoria_id' => $otraSub->id]))
            ->assertSessionHasNoErrors();
    }

    public function test_no_enlaza_con_catalogos_de_otro_tenant(): void
    {
        $ajeno = $this->catalogosBasicosDe(Tenant::factory()->create());

        $this->post(route('gestion.productos.store'), $this->datos([
            'medida_id' => $ajeno['medida']->id,
            'categoria_id' => $ajeno['categoria']->id,
            'sub_categoria_id' => $ajeno['subcategoria']->id,
        ]))->assertSessionHasErrors(['medida_id', 'categoria_id']);

        $this->assertDatabaseCount('productos', 0);
    }

    public function test_valida_los_valores_permitidos_y_los_precios(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos([
            'categoria_producto' => 'MAGIA',
            'volumen_producto' => 'ENORME',
            'tipo_compra' => 'CONTRABANDO',
            'valor_detal_producto' => '-5',
            'iva_producto' => '150',
        ]))->assertSessionHasErrors(['categoria_producto', 'volumen_producto', 'tipo_compra', 'valor_detal_producto', 'iva_producto']);
    }

    public function test_el_tipo_es_texto_libre_y_el_tipo_de_compra_es_nacional_o_importado(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos(['tipo_producto' => 'Ferretería pesada', 'tipo_compra' => 'IMPORTADO']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos', ['tipo_producto' => 'Ferretería pesada', 'tipo_compra' => 'IMPORTADO']);

        $this->post(route('gestion.productos.store'), $this->datos(['tipo_compra' => 'NACIONAL']))->assertSessionHasNoErrors();
        $this->post(route('gestion.productos.store'), $this->datos(['tipo_compra' => '']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productos', ['tipo_compra' => 'NACIONAL']);
    }

    public function test_el_producto_ya_no_se_asocia_a_una_bodega(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos(['bodega_id' => $this->catalogos['bodega']->id]))
            ->assertSessionHasNoErrors();

        $this->assertFalse(Schema::hasColumn('productos', 'bodega_id'));
    }

    public function test_guarda_reemplaza_y_quita_la_imagen(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos(['imagen' => UploadedFile::fake()->image('foto.png')]));
        $producto = Producto::firstOrFail();
        $primera = $producto->imagen_producto;
        Storage::disk('public')->assertExists($primera);
        $this->assertStringStartsWith("productos/{$this->tenant->id}/", $primera);

        $this->put(route('gestion.productos.update', $producto), $this->datos(['imagen' => UploadedFile::fake()->image('otra.png')]));
        $segunda = $producto->refresh()->imagen_producto;
        Storage::disk('public')->assertMissing($primera);
        Storage::disk('public')->assertExists($segunda);

        $this->put(route('gestion.productos.update', $producto), $this->datos(['quitar_imagen' => '1']));
        $this->assertNull($producto->refresh()->imagen_producto);
        Storage::disk('public')->assertMissing($segunda);
    }

    public function test_rechaza_una_imagen_que_no_lo_es(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos(['imagen' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('imagen');
    }

    public function test_el_listado_trae_los_catalogos_del_formulario_con_subcategorias_anidadas(): void
    {
        $this->crearProductoEn($this->tenant, $this->catalogos);

        $this->get(route('gestion.productos.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gestion/productos/index')
                ->has('productos.data', 1)
                ->where('productos.data.0.categoria', 'Herramientas')
                ->where('productos.data.0.sub_categoria', 'Manuales')
                ->has('medidas', 1)
                ->missing('bodegas')
                ->where('categorias.0.nombre_categoria', 'Herramientas')
                ->where('categorias.0.sub_categorias.0.nombre_sub_categoria', 'Manuales'));
    }

    public function test_el_listado_busca_y_separa_activos_de_eliminados(): void
    {
        $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'A', 'nombre_producto' => 'Martillo']);
        $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'B', 'nombre_producto' => 'Serrucho']);
        $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'C', 'nombre_producto' => 'Cincel'])->delete();

        $this->get(route('gestion.productos.index', ['buscar' => 'Martillo']))
            ->assertInertia(fn ($page) => $page->has('productos.data', 1)->where('productos.data.0.codigo_producto', 'A'));

        $this->get(route('gestion.productos.index', ['eliminados' => 1]))
            ->assertInertia(fn ($page) => $page->has('productos.data', 1)->where('productos.data.0.codigo_producto', 'C'));
    }

    public function test_el_listado_filtra_por_pestana_de_tipo_y_cuenta_cada_una(): void
    {
        $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'MP-001', 'categoria_producto' => Producto::MATERIA_PRIMA]);
        $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'MP-002', 'categoria_producto' => Producto::MATERIA_PRIMA]);
        $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'PT-001', 'categoria_producto' => 'PRODUCTO_TERMINADO']);

        $this->get(route('gestion.productos.index', ['tipo' => Producto::MATERIA_PRIMA]))
            ->assertInertia(fn ($page) => $page
                ->has('productos.data', 2)
                ->where('filtros.tipo', Producto::MATERIA_PRIMA)
                ->where('conteoPorTipo.'.Producto::MATERIA_PRIMA, 2)
                ->where('conteoPorTipo.PRODUCTO_TERMINADO', 1));

        // Un tipo desconocido equivale a "todos".
        $this->get(route('gestion.productos.index', ['tipo' => 'INVENTADO']))
            ->assertInertia(fn ($page) => $page->has('productos.data', 3)->where('filtros.tipo', null));
    }

    public function test_elimina_de_forma_logica_y_restaura(): void
    {
        $producto = $this->crearProductoEn($this->tenant, $this->catalogos);

        $this->delete(route('gestion.productos.destroy', $producto))->assertRedirect(route('gestion.productos.index'));
        $this->assertSoftDeleted($producto);

        $this->post(route('gestion.productos.restore', $producto))->assertRedirect(route('gestion.productos.index'));
        $this->assertNotSoftDeleted($producto);
    }

    public function test_no_toca_productos_de_otro_tenant(): void
    {
        $otroTenant = Tenant::factory()->create();
        $ajeno = $this->crearProductoEn($otroTenant, $this->catalogosBasicosDe($otroTenant));

        $this->put(route('gestion.productos.update', $ajeno), $this->datos())->assertNotFound();
        $this->delete(route('gestion.productos.destroy', $ajeno))->assertNotFound();
        $this->post(route('gestion.productos.restore', $ajeno))->assertNotFound();
    }

    public function test_exige_permiso_por_accion(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['productos.ver']));

        $this->get(route('gestion.productos.index'))->assertOk();
        $this->post(route('gestion.productos.store'), $this->datos())->assertForbidden();
    }
}
