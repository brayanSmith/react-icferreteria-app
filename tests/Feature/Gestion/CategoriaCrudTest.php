<?php

namespace Tests\Feature\Gestion;

use App\Models\Categoria;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaCrudTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararGestion();
        $this->actingAs($this->administradorDe($this->tenant));
    }

    public function test_crea_una_categoria(): void
    {
        $this->post(route('gestion.categorias.store'), ['nombre_categoria' => 'Pinturas'])
            ->assertRedirect(route('gestion.categorias.index'));

        $this->assertDatabaseHas('categorias', ['tenant_id' => $this->tenant->id, 'nombre_categoria' => 'Pinturas']);
    }

    public function test_el_nombre_es_unico_en_el_tenant_pero_repetible_en_otro(): void
    {
        $this->crearEn(Categoria::class, $this->tenant, ['nombre_categoria' => 'Pinturas']);

        $this->post(route('gestion.categorias.store'), ['nombre_categoria' => 'Pinturas'])
            ->assertSessionHasErrors('nombre_categoria');

        $this->actingAs($this->administradorDe(Tenant::factory()->create()));
        $this->post(route('gestion.categorias.store'), ['nombre_categoria' => 'Pinturas'])->assertSessionHasNoErrors();
    }

    public function test_el_listado_trae_las_subcategorias_de_cada_categoria(): void
    {
        $categoria = $this->crearEn(Categoria::class, $this->tenant, ['nombre_categoria' => 'Pinturas']);
        $categoria->subCategorias()->create(['nombre_sub_categoria' => 'Esmaltes']);
        $this->crearEn(Categoria::class, Tenant::factory()->create(), ['nombre_categoria' => 'Ajena']);

        $this->get(route('gestion.categorias.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gestion/categorias/index')
                ->has('categorias.data', 1)
                ->where('categorias.data.0.sub_categorias.0.nombre_sub_categoria', 'Esmaltes'));
    }

    public function test_actualiza_y_elimina_una_categoria_sin_productos(): void
    {
        $categoria = $this->crearEn(Categoria::class, $this->tenant, ['nombre_categoria' => 'Pinturas']);

        $this->put(route('gestion.categorias.update', $categoria), ['nombre_categoria' => 'Pinturas y más'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Pinturas y más', $categoria->refresh()->nombre_categoria);

        $this->delete(route('gestion.categorias.destroy', $categoria))->assertRedirect(route('gestion.categorias.index'));
        $this->assertModelMissing($categoria);
    }

    public function test_no_elimina_una_categoria_que_usan_productos(): void
    {
        $catalogos = $this->catalogosBasicosDe($this->tenant);
        $this->crearProductoEn($this->tenant, $catalogos);

        $this->delete(route('gestion.categorias.destroy', $catalogos['categoria']))->assertRedirect();

        $this->assertModelExists($catalogos['categoria']);
        $this->assertDatabaseCount('productos', 1);
    }

    public function test_crea_actualiza_y_elimina_subcategorias(): void
    {
        $categoria = $this->crearEn(Categoria::class, $this->tenant, ['nombre_categoria' => 'Pinturas']);

        $this->post(route('gestion.categorias.subcategorias.store', $categoria), ['nombre_sub_categoria' => 'Esmaltes'])
            ->assertRedirect(route('gestion.categorias.index'));
        $sub = $categoria->subCategorias()->firstOrFail();

        $this->put(route('gestion.categorias.subcategorias.update', [$categoria, $sub]), ['nombre_sub_categoria' => 'Vinilos'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Vinilos', $sub->refresh()->nombre_sub_categoria);

        $this->delete(route('gestion.categorias.subcategorias.destroy', [$categoria, $sub]))
            ->assertRedirect(route('gestion.categorias.index'));
        $this->assertModelMissing($sub);
    }

    public function test_el_nombre_de_subcategoria_es_unico_solo_dentro_de_su_categoria(): void
    {
        $pinturas = $this->crearEn(Categoria::class, $this->tenant, ['nombre_categoria' => 'Pinturas']);
        $herramientas = $this->crearEn(Categoria::class, $this->tenant, ['nombre_categoria' => 'Herramientas']);
        $pinturas->subCategorias()->create(['nombre_sub_categoria' => 'Otros']);

        $this->post(route('gestion.categorias.subcategorias.store', $pinturas), ['nombre_sub_categoria' => 'Otros'])
            ->assertSessionHasErrors('nombre_sub_categoria');

        $this->post(route('gestion.categorias.subcategorias.store', $herramientas), ['nombre_sub_categoria' => 'Otros'])
            ->assertSessionHasNoErrors();
    }

    public function test_no_elimina_una_subcategoria_que_usan_productos(): void
    {
        $catalogos = $this->catalogosBasicosDe($this->tenant);
        $this->crearProductoEn($this->tenant, $catalogos);

        $this->delete(route('gestion.categorias.subcategorias.destroy', [$catalogos['categoria'], $catalogos['subcategoria']]))
            ->assertRedirect();

        $this->assertModelExists($catalogos['subcategoria']);
    }

    public function test_no_alcanza_categorias_ni_subcategorias_de_otro_tenant(): void
    {
        $ajena = $this->crearEn(Categoria::class, Tenant::factory()->create(), ['nombre_categoria' => 'Ajena']);
        $subAjena = $ajena->subCategorias()->create(['nombre_sub_categoria' => 'Ajena sub']);

        $this->put(route('gestion.categorias.update', $ajena), ['nombre_categoria' => 'X'])->assertNotFound();
        $this->post(route('gestion.categorias.subcategorias.store', $ajena), ['nombre_sub_categoria' => 'X'])->assertNotFound();
        $this->delete(route('gestion.categorias.subcategorias.destroy', [$ajena, $subAjena]))->assertNotFound();
    }

    public function test_una_subcategoria_no_se_alcanza_por_una_categoria_que_no_es_su_padre(): void
    {
        $pinturas = $this->crearEn(Categoria::class, $this->tenant, ['nombre_categoria' => 'Pinturas']);
        $herramientas = $this->crearEn(Categoria::class, $this->tenant, ['nombre_categoria' => 'Herramientas']);
        $sub = $pinturas->subCategorias()->create(['nombre_sub_categoria' => 'Esmaltes']);

        $this->delete(route('gestion.categorias.subcategorias.destroy', [$herramientas, $sub]))->assertNotFound();
    }

    public function test_exige_permiso_por_accion(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['categorias.ver']));
        $categoria = $this->crearEn(Categoria::class, $this->tenant, ['nombre_categoria' => 'Pinturas']);

        $this->get(route('gestion.categorias.index'))->assertOk();
        $this->post(route('gestion.categorias.store'), ['nombre_categoria' => 'X'])->assertForbidden();
        $this->post(route('gestion.categorias.subcategorias.store', $categoria), ['nombre_sub_categoria' => 'X'])->assertForbidden();
    }
}
