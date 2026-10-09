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
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function datos(array $sobrescribir = []): array
    {
        return [
            'categoria_producto' => 'PRODUCTO_TERMINADO',
            'codigo_producto' => 'MAR-001',
            'nombre_producto' => 'Martillo de acero',
            'costo_producto' => '10000',
            'valor_detal_producto' => '15000',
            'medida_id' => $this->catalogos['medida']->id,
            'bodega_id' => $this->catalogos['bodega']->id,
            'categoria_id' => $this->catalogos['categoria']->id,
            'sub_categoria_id' => $this->catalogos['subcategoria']->id,
            'activo' => '1',
            ...$sobrescribir,
        ];
    }

    public function test_crea_un_producto_con_numero_consecutivo_y_texto_concatenado(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos())
            ->assertRedirect(route('gestion.productos.index'));

        $producto = Producto::firstOrFail();

        $this->assertSame($this->tenant->id, $producto->tenant_id);
        $this->assertSame(1, $producto->numero);
        $this->assertSame('MAR-001 - Martillo de acero', $producto->concatenar_codigo_nombre);
        $this->assertEquals(1, $producto->activo);
    }

    public function test_los_campos_numericos_vacios_se_guardan_como_cero(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos([
            'costo_producto' => '',
            'valor_detal_producto' => '',
            'alerta_producto' => '',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos', [
            'codigo_producto' => 'MAR-001',
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
            'bodega_id' => $ajeno['bodega']->id,
            'categoria_id' => $ajeno['categoria']->id,
            'sub_categoria_id' => $ajeno['subcategoria']->id,
        ]))->assertSessionHasErrors(['medida_id', 'bodega_id', 'categoria_id']);

        $this->assertDatabaseCount('productos', 0);
    }

    public function test_no_acepta_una_bodega_eliminada(): void
    {
        $this->catalogos['bodega']->delete();

        $this->post(route('gestion.productos.store'), $this->datos())->assertSessionHasErrors('bodega_id');
    }

    public function test_el_codigo_es_unico_en_el_tenant_pero_repetible_en_otro(): void
    {
        $this->crearProductoEn($this->tenant, $this->catalogos, ['codigo_producto' => 'MAR-001']);

        $this->post(route('gestion.productos.store'), $this->datos())->assertSessionHasErrors('codigo_producto');

        $otroTenant = Tenant::factory()->create();
        $this->crearProductoEn($otroTenant, $this->catalogosBasicosDe($otroTenant), ['codigo_producto' => 'MAR-001']);
        $this->assertDatabaseCount('productos', 2);
    }

    public function test_valida_los_valores_permitidos_y_los_precios(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos([
            'categoria_producto' => 'MAGIA',
            'volumen_producto' => 'ENORME',
            'tipo_compra' => 'CONTRABANDO',
            'tipo_producto' => 'CONTRABANDO',
            'valor_detal_producto' => '-5',
            'iva_producto' => '150',
        ]))->assertSessionHasErrors(['categoria_producto', 'volumen_producto', 'tipo_compra', 'tipo_producto', 'valor_detal_producto', 'iva_producto']);
    }

    public function test_el_tipo_del_producto_es_nacional_o_importado(): void
    {
        $this->post(route('gestion.productos.store'), $this->datos(['codigo_producto' => 'N-1', 'tipo_producto' => 'NACIONAL']))
            ->assertSessionHasNoErrors();
        $this->post(route('gestion.productos.store'), $this->datos(['codigo_producto' => 'I-1', 'tipo_producto' => 'IMPORTADO']))
            ->assertSessionHasNoErrors();
        $this->post(route('gestion.productos.store'), $this->datos(['codigo_producto' => 'S-1', 'tipo_producto' => '']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos', ['codigo_producto' => 'N-1', 'tipo_producto' => 'NACIONAL']);
        $this->assertDatabaseHas('productos', ['codigo_producto' => 'I-1', 'tipo_producto' => 'IMPORTADO']);
        $this->assertDatabaseHas('productos', ['codigo_producto' => 'S-1', 'tipo_producto' => null]);
    }

    public function test_actualiza_un_producto_y_recalcula_el_texto_concatenado(): void
    {
        $producto = $this->crearProductoEn($this->tenant, $this->catalogos);

        $this->put(route('gestion.productos.update', $producto), $this->datos(['codigo_producto' => 'P-001', 'nombre_producto' => 'Martillo grande']))
            ->assertSessionHasNoErrors();

        $this->assertSame('P-001 - Martillo grande', $producto->refresh()->concatenar_codigo_nombre);
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
                ->has('bodegas', 1)
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

    public function test_elimina_de_forma_logica_y_restaura(): void
    {
        $producto = $this->crearProductoEn($this->tenant, $this->catalogos);

        $this->delete(route('gestion.productos.destroy', $producto))->assertRedirect(route('gestion.productos.index'));
        $this->assertSoftDeleted($producto);

        $this->post(route('gestion.productos.store'), $this->datos(['codigo_producto' => 'P-001']))->assertSessionHasErrors('codigo_producto');
        $this->assertStringContainsString('restáuralo', session('errors')->first('codigo_producto'));

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
