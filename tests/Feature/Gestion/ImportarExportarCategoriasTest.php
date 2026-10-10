<?php

namespace Tests\Feature\Gestion;

use App\Models\Categoria;
use App\Models\Importacion;
use App\Models\SubCategoria;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportarExportarCategoriasTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->prepararGestion();
        $this->actingAs($this->administradorDe($this->tenant));
    }

    /**
     * Paso 1: sube el archivo y devuelve la importación que espera configuración.
     */
    private function subirArchivo(string $contenido, string $modulo = 'categorias'): Importacion
    {
        $this->post(route('gestion.importar.store', $modulo), [
            'archivo' => UploadedFile::fake()->createWithContent('archivo.csv', $contenido),
        ])->assertSessionHasNoErrors();

        return Importacion::latest('id')->firstOrFail();
    }

    /**
     * Pasos 1 y 2. Si no se indica el mapeo, se usa el que el sistema sugiere.
     *
     * @param  array<string, int|null>|null  $mapeo
     */
    private function importar(string $contenido, string $modulo = 'categorias', ?array $mapeo = null, string $modo = 'crear'): Importacion
    {
        $importacion = $this->subirArchivo($contenido, $modulo);

        $this->post(route('gestion.importar.confirmar', $importacion), [
            'mapeo' => $mapeo ?? $this->sugerencia(),
            'modo' => $modo,
        ])->assertSessionHasNoErrors();

        return $importacion->refresh();
    }

    /**
     * @return array<string, int|null>
     */
    private function sugerencia(): array
    {
        return $this->get(route('gestion.categorias.index'))->viewData('page')['props']['importacionPendiente']['sugerencia'];
    }

    private function categoria(string $nombre, ?Tenant $tenant = null): Categoria
    {
        return $this->crearEn(Categoria::class, $tenant ?? $this->tenant, ['nombre_categoria' => $nombre]);
    }

    // --- Categorías -----------------------------------------------------------

    public function test_importa_categorias_de_un_csv_separado_por_punto_y_coma(): void
    {
        $importacion = $this->importar("Categoría;Otra\nHerramientas;x\nPinturas;y\n");

        $this->assertSame(['Herramientas', 'Pinturas'], Categoria::orderBy('nombre_categoria')->pluck('nombre_categoria')->all());
        $this->assertSame(Importacion::COMPLETADA, $importacion->estado);
        $this->assertSame(';', $importacion->delimitador);
        $this->assertSame(2, $importacion->filas_creadas);
        $this->assertNull($importacion->ruta);
    }

    public function test_importa_un_csv_separado_por_comas_y_acepta_los_encabezados_como_claves(): void
    {
        $importacion = $this->importar("categoria\nHerramientas\n");

        $this->assertSame(',', $importacion->delimitador);
        $this->assertDatabaseHas('categorias', ['nombre_categoria' => 'Herramientas']);
    }

    public function test_lee_archivos_guardados_por_excel_en_windows_1252(): void
    {
        $this->importar(mb_convert_encoding("Categoría\nFerretería\n", 'Windows-1252', 'UTF-8'));

        $this->assertDatabaseHas('categorias', ['nombre_categoria' => 'Ferretería']);
    }

    public function test_subir_el_archivo_detecta_las_columnas_y_sugiere_la_relacion(): void
    {
        $this->subirArchivo("Nombre categoria;Notas\nHerramientas;x\n");

        $this->get(route('gestion.categorias.index'))
            ->assertInertia(fn ($page) => $page
                ->where('importacionPendiente.etiqueta', 'Categorías')
                ->where('importacionPendiente.encabezados', ['Nombre categoria', 'Notas'])
                ->where('importacionPendiente.delimitador', ';')
                ->where('importacionPendiente.muestra.0', ['Herramientas', 'x'])
                ->where('importacionPendiente.sugerencia.categoria', 0)
                ->where('importacionPendiente.sugerencia.id_categoria', null)
                ->has('importacionPendiente.columnas', 2)
                ->has('importaciones.data', 0));

        $this->assertSame(Importacion::CONFIGURANDO, Importacion::firstOrFail()->estado);
        $this->assertSame(0, Categoria::count());
    }

    public function test_importa_con_columnas_que_no_se_llaman_como_el_sistema(): void
    {
        // El archivo trae las columnas en otro orden y con otros nombres: el usuario las relaciona.
        $importacion = $this->importar(
            "Codigo interno;Rubro\n77;Herramientas\n",
            mapeo: ['categoria' => 1, 'id_categoria' => null],
        );

        $this->assertSame(Importacion::COMPLETADA, $importacion->estado);
        $this->assertDatabaseHas('categorias', ['nombre_categoria' => 'Herramientas']);
    }

    public function test_exige_relacionar_las_columnas_obligatorias(): void
    {
        $importacion = $this->subirArchivo("Rubro;Familia\nHerramientas;Manuales\n");

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['id_categoria' => 1], 'modo' => 'crear'])
            ->assertSessionHasErrors('mapeo.categoria');

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['categoria' => 5], 'modo' => 'crear'])
            ->assertSessionHasErrors('mapeo.categoria');

        $this->assertSame(Importacion::CONFIGURANDO, $importacion->refresh()->estado);
    }

    public function test_cancelar_borra_el_archivo_y_la_configuracion_pendiente(): void
    {
        $importacion = $this->subirArchivo("Categoría\nHerramientas\n");
        $ruta = $importacion->ruta;
        Storage::disk('local')->assertExists($ruta);

        $this->delete(route('gestion.importar.destroy', $importacion))->assertRedirect();

        Storage::disk('local')->assertMissing($ruta);
        $this->assertSame(0, Importacion::count());
    }

    public function test_subir_otro_archivo_descarta_la_configuracion_anterior(): void
    {
        $primera = $this->subirArchivo("Categoría\nA\n");
        $this->subirArchivo("Categoría\nB\n", 'subcategorias');

        $this->assertSame(1, Importacion::count());
        Storage::disk('local')->assertMissing($primera->ruta);
    }

    public function test_un_archivo_vacio_se_rechaza(): void
    {
        $this->post(route('gestion.importar.store', 'categorias'), [
            'archivo' => UploadedFile::fake()->createWithContent('vacio.csv', ''),
        ])->assertSessionHasErrors('archivo');

        $this->assertSame(0, Importacion::count());
    }

    public function test_importar_dos_veces_no_duplica_y_cuenta_lo_omitido(): void
    {
        $contenido = "Categoría\nHerramientas\n";
        $this->importar($contenido);
        $segunda = $this->importar($contenido);

        $this->assertSame(1, Categoria::count());
        $this->assertSame(0, $segunda->filas_creadas);
        $this->assertSame(1, $segunda->filas_omitidas);
    }

    public function test_reporta_las_filas_con_error_sin_detener_el_resto(): void
    {
        $importacion = $this->importar("Categoría;Otra\n;vacia\nValida;x\n".str_repeat('x', 101).";y\n");

        $this->assertSame(Importacion::COMPLETADA, $importacion->estado);
        $this->assertSame(1, $importacion->filas_creadas);
        $this->assertSame(2, $importacion->filas_con_error);
        $this->assertSame([2, 4], array_column($importacion->errores, 'fila'));
        $this->assertDatabaseHas('categorias', ['nombre_categoria' => 'Valida']);
    }

    public function test_importa_en_la_empresa_del_usuario_y_no_toca_la_de_otro_tenant(): void
    {
        $ajena = $this->categoria('Herramientas', Tenant::factory()->create());

        $this->importar("Categoría\nHerramientas\n");

        $propia = Categoria::query()->where('nombre_categoria', 'Herramientas')->firstOrFail();
        $this->assertSame($this->tenant->id, $propia->tenant_id);
        $this->assertNotSame($ajena->id, $propia->id);
    }

    public function test_actualizar_renombra_categorias_por_su_id(): void
    {
        $categoria = $this->categoria('Herramientas');
        $sinCambios = $this->categoria('Pinturas');

        $importacion = $this->importar(
            "ID categoría;Categoría\n{$categoria->id};Ferretería\n{$sinCambios->id};Pinturas\n",
            modo: 'actualizar',
        );

        $this->assertSame('actualizar', $importacion->modo);
        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertSame(1, $importacion->filas_omitidas);
        $this->assertSame(0, $importacion->filas_creadas);
        $this->assertSame('Ferretería', $categoria->refresh()->nombre_categoria);
        $this->assertSame(2, Categoria::count());
    }

    public function test_actualizar_categorias_rechaza_ids_inexistentes_ajenos_y_nombres_repetidos(): void
    {
        $uno = $this->categoria('Uno');
        $this->categoria('Dos');
        $ajena = $this->categoria('Ajena', Tenant::factory()->create());

        $importacion = $this->importar(
            "ID categoría;Categoría\n9999;Nueva\n{$ajena->id};Robada\n{$uno->id};Dos\n",
            modo: 'actualizar',
        );

        $this->assertSame(3, $importacion->filas_con_error);
        $this->assertSame('Ajena', $ajena->refresh()->nombre_categoria);
        $this->assertSame('Uno', $uno->refresh()->nombre_categoria);
        $this->assertStringContainsString('No existe una categoría', $importacion->errores[0]['mensaje']);
        $this->assertStringContainsString('Ya existe otra categoría', $importacion->errores[2]['mensaje']);
    }

    public function test_actualizar_exige_relacionar_el_id_de_la_categoria(): void
    {
        $importacion = $this->subirArchivo("Categoría\nHerramientas\n");

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['categoria' => 0], 'modo' => 'actualizar'])
            ->assertSessionHasErrors('mapeo.id_categoria');

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['categoria' => 0], 'modo' => 'crear'])
            ->assertSessionHasNoErrors();
    }

    public function test_un_mismo_archivo_puede_crear_y_actualizar_categorias(): void
    {
        $existente = $this->categoria('Herramientas');
        $this->categoria('Pinturas');

        $importacion = $this->importar(
            "ID categoría;Categoría
{$existente->id};Ferretería
;Pinturas
;Plomería
9999;Fantasma
",
            modo: 'ambos',
        );

        // Con ID actualiza; sin ID crea (y omite lo que ya existe); un ID inexistente se rechaza.
        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertSame(1, $importacion->filas_creadas);
        $this->assertSame(1, $importacion->filas_omitidas);
        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertSame('Ferretería', $existente->refresh()->nombre_categoria);
        $this->assertDatabaseHas('categorias', ['nombre_categoria' => 'Plomería']);
    }

    public function test_un_mismo_archivo_puede_crear_y_actualizar_subcategorias(): void
    {
        $categoria = $this->categoria('Herramientas');
        $sub = SubCategoria::create(['categoria_id' => $categoria->id, 'nombre_sub_categoria' => 'Manuales']);

        $importacion = $this->importar(
            "ID subcategoría;Categoría;Subcategoría
{$sub->id};;A mano
;Herramientas;Eléctricas
;Fantasma;Nada
",
            'subcategorias',
            modo: 'ambos',
        );

        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertSame(1, $importacion->filas_creadas);
        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertSame('A mano', $sub->refresh()->nombre_sub_categoria);
        $this->assertDatabaseHas('sub_categorias', ['nombre_sub_categoria' => 'Eléctricas']);
    }

    public function test_el_modo_ambos_exige_relacionar_el_id_y_un_modo_desconocido_se_rechaza(): void
    {
        $importacion = $this->subirArchivo('Categoría
Herramientas
');

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['categoria' => 0], 'modo' => 'ambos'])
            ->assertSessionHasErrors('mapeo.id_categoria');

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['categoria' => 0], 'modo' => 'borrar'])
            ->assertSessionHasErrors('modo');
    }

    // --- Subcategorías --------------------------------------------------------

    public function test_importa_subcategorias_en_sus_categorias(): void
    {
        $herramientas = $this->categoria('Herramientas');
        $this->categoria('Pinturas');

        $importacion = $this->importar(
            "Categoría;Subcategoría\nHerramientas;Manuales\nherramientas;Eléctricas\nPinturas;Esmaltes\n",
            'subcategorias',
        );

        $this->assertSame(3, $importacion->filas_creadas);
        $this->assertSame(2, SubCategoria::where('categoria_id', $herramientas->id)->count());
        $this->assertSame(0, Categoria::count() - 2, 'No debe crear categorías.');
    }

    public function test_las_subcategorias_rechazan_una_categoria_que_no_existe_y_omiten_las_repetidas(): void
    {
        $herramientas = $this->categoria('Herramientas');
        SubCategoria::create(['categoria_id' => $herramientas->id, 'nombre_sub_categoria' => 'Manuales']);

        $importacion = $this->importar(
            "Categoría;Subcategoría\nHerramientas;Manuales\nFantasma;Algo\n",
            'subcategorias',
        );

        $this->assertSame(0, $importacion->filas_creadas);
        $this->assertSame(1, $importacion->filas_omitidas);
        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertStringContainsString('Fantasma no existe', $importacion->errores[0]['mensaje']);
        $this->assertSame(1, SubCategoria::count());
    }

    public function test_la_misma_subcategoria_puede_existir_en_categorias_distintas(): void
    {
        $this->categoria('Herramientas');
        $this->categoria('Pinturas');

        $this->importar("Categoría;Subcategoría\nHerramientas;Otros\nPinturas;Otros\n", 'subcategorias');

        $this->assertSame(2, SubCategoria::where('nombre_sub_categoria', 'Otros')->count());
    }

    public function test_actualizar_renombra_subcategorias_por_su_id_y_no_toca_las_ajenas(): void
    {
        $categoria = $this->categoria('Herramientas');
        $sub = SubCategoria::create(['categoria_id' => $categoria->id, 'nombre_sub_categoria' => 'Manuales']);
        SubCategoria::create(['categoria_id' => $categoria->id, 'nombre_sub_categoria' => 'Eléctricas']);
        $ajena = SubCategoria::create(['categoria_id' => $this->categoria('Ajena', Tenant::factory()->create())->id, 'nombre_sub_categoria' => 'Ajeno']);

        $importacion = $this->importar(
            "ID subcategoría;Subcategoría\n{$sub->id};A mano\n{$ajena->id};Robada\n9999;Nada\n",
            'subcategorias',
            modo: 'actualizar',
        );

        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertSame(2, $importacion->filas_con_error);
        $this->assertSame('A mano', $sub->refresh()->nombre_sub_categoria);
        $this->assertSame('Ajeno', $ajena->refresh()->nombre_sub_categoria);

        $repetida = $this->importar("ID subcategoría;Subcategoría\n{$sub->id};Eléctricas\n", 'subcategorias', modo: 'actualizar');
        $this->assertStringContainsString('Ya existe otra subcategoría', $repetida->errores[0]['mensaje']);
    }

    public function test_el_listado_trae_las_importaciones_recientes_de_ambos_tipos(): void
    {
        $this->categoria('Herramientas');
        $this->importar("Categoría\nPinturas\n");
        $this->importar("Categoría;Subcategoría\nHerramientas;Manuales\n", 'subcategorias');

        $this->get(route('gestion.categorias.index'))
            ->assertInertia(fn ($page) => $page
                ->has('importaciones.data', 2)
                ->where('importaciones.data.0.tipo', 'Subcategorías')
                ->where('importaciones.data.1.tipo', 'Categorías')
                ->where('importacionPendiente', null));
    }

    // --- Archivos, permisos y exportación ------------------------------------

    public function test_rechaza_un_archivo_que_no_es_csv(): void
    {
        $this->post(route('gestion.importar.store', 'categorias'), [
            'archivo' => UploadedFile::fake()->image('foto.png'),
        ])->assertSessionHasErrors('archivo');

        $this->assertSame(0, Importacion::count());
    }

    public function test_un_modulo_desconocido_da_404(): void
    {
        $this->get(route('gestion.importar.plantilla', 'inventado'))->assertNotFound();
        $this->get(route('gestion.exportar', ['modulo' => 'inventado', 'formato' => 'csv']))->assertNotFound();
        $this->get(route('gestion.exportar', ['modulo' => 'categorias', 'formato' => 'doc']))->assertNotFound();
    }

    public function test_descarga_la_plantilla_de_cada_tipo(): void
    {
        $categorias = $this->get(route('gestion.importar.plantilla', 'categorias'));
        $this->assertStringContainsString('"ID categoría";Categoría', $categorias->streamedContent());

        $subcategorias = $this->get(route('gestion.importar.plantilla', 'subcategorias'));
        $this->assertStringContainsString('"ID subcategoría";Categoría;Subcategoría', $subcategorias->streamedContent());
    }

    public function test_exporta_en_csv_y_txt_con_los_ids(): void
    {
        $categoria = $this->categoria('Herramientas');
        $sub = SubCategoria::create(['categoria_id' => $categoria->id, 'nombre_sub_categoria' => 'Manuales']);
        $pinturas = $this->categoria('Pinturas');
        $this->categoria('Ajena', Tenant::factory()->create());

        $csv = $this->get(route('gestion.exportar', ['modulo' => 'categorias', 'formato' => 'csv']));
        $csv->assertOk()->assertDownload();
        $contenido = $csv->streamedContent();
        $this->assertStringContainsString("\"ID categoría\";Categoría;\"ID subcategoría\";Subcategoría\n", $contenido);
        $this->assertStringContainsString("{$categoria->id};Herramientas;{$sub->id};Manuales\n", $contenido);
        $this->assertStringContainsString("{$pinturas->id};Pinturas;;\n", $contenido);
        $this->assertStringNotContainsString('Ajena', $contenido);

        $txt = $this->get(route('gestion.exportar', ['modulo' => 'categorias', 'formato' => 'txt']));
        $this->assertStringContainsString("Herramientas\t{$sub->id}\tManuales", $txt->streamedContent());
    }

    public function test_un_archivo_exportado_se_puede_editar_y_reimportar_para_actualizar(): void
    {
        $categoria = $this->categoria('Herramientas');
        $sub = SubCategoria::create(['categoria_id' => $categoria->id, 'nombre_sub_categoria' => 'Manuales']);

        $exportado = $this->get(route('gestion.exportar', ['modulo' => 'categorias', 'formato' => 'csv']))->streamedContent();

        $importacion = $this->importar(str_replace('Manuales', 'A mano', $exportado), 'subcategorias', modo: 'actualizar');
        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertSame('A mano', $sub->refresh()->nombre_sub_categoria);

        $importacion = $this->importar(str_replace('Herramientas', 'Ferretería', $exportado), modo: 'actualizar');
        $this->assertSame('Ferretería', $categoria->refresh()->nombre_categoria);
        $this->assertSame(0, $importacion->filas_con_error);
    }

    public function test_exporta_en_xlsx_y_pdf(): void
    {
        $this->categoria('Herramientas');

        $xlsx = $this->get(route('gestion.exportar', ['modulo' => 'categorias', 'formato' => 'xlsx']));
        $xlsx->assertOk()->assertDownload();
        $this->assertStringStartsWith('PK', $xlsx->streamedContent());

        $pdf = $this->get(route('gestion.exportar', ['modulo' => 'categorias', 'formato' => 'pdf']));
        $pdf->assertOk()->assertDownload();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_importar_exige_crear_y_exportar_exige_ver(): void
    {
        $pendiente = $this->subirArchivo("Categoría\nX\n");

        $this->actingAs($this->personalConPermisos($this->tenant, ['categorias.ver']));

        $this->get(route('gestion.exportar', ['modulo' => 'categorias', 'formato' => 'csv']))->assertOk();
        $this->get(route('gestion.importar.plantilla', 'categorias'))->assertForbidden();
        $this->post(route('gestion.importar.store', 'subcategorias'), [
            'archivo' => UploadedFile::fake()->createWithContent('c.csv', "Categoría\nX\n"),
        ])->assertForbidden();
        $this->post(route('gestion.importar.confirmar', $pendiente), ['mapeo' => ['categoria' => 0], 'modo' => 'crear'])->assertForbidden();
        $this->delete(route('gestion.importar.destroy', $pendiente))->assertForbidden();

        $this->actingAs($this->personalConPermisos($this->tenant, ['medidas.ver']));
        $this->get(route('gestion.exportar', ['modulo' => 'categorias', 'formato' => 'csv']))->assertForbidden();
    }

    public function test_actualizar_por_importacion_exige_ademas_el_permiso_de_editar(): void
    {
        $pendiente = $this->subirArchivo('ID categoría;Categoría
1;X
');

        $this->actingAs($this->personalConPermisos($this->tenant, ['categorias.ver', 'categorias.crear']));

        $this->post(route('gestion.importar.confirmar', $pendiente), ['mapeo' => ['id_categoria' => 0, 'categoria' => 1], 'modo' => 'actualizar'])->assertForbidden();
        $this->post(route('gestion.importar.confirmar', $pendiente), ['mapeo' => ['id_categoria' => 0, 'categoria' => 1], 'modo' => 'ambos'])->assertForbidden();
        $this->post(route('gestion.importar.confirmar', $pendiente), ['mapeo' => ['categoria' => 1], 'modo' => 'crear'])->assertSessionHasNoErrors();

        $this->actingAs($this->personalConPermisos($this->tenant, ['categorias.ver', 'categorias.crear', 'categorias.editar']));
        $otro = $this->subirArchivo('ID categoría;Categoría
1;X
');
        $this->post(route('gestion.importar.confirmar', $otro), ['mapeo' => ['id_categoria' => 0, 'categoria' => 1], 'modo' => 'actualizar'])->assertSessionHasNoErrors();
    }

    public function test_no_se_puede_confirmar_la_importacion_de_otro_tenant(): void
    {
        $pendiente = $this->subirArchivo("Categoría\nX\n");

        $this->actingAs($this->administradorDe(Tenant::factory()->create()));

        $this->post(route('gestion.importar.confirmar', $pendiente), ['mapeo' => ['categoria' => 0], 'modo' => 'crear'])->assertNotFound();
    }
}
