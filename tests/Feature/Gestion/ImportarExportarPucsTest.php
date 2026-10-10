<?php

namespace Tests\Feature\Gestion;

use App\Models\Importacion;
use App\Models\Puc;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportarExportarPucsTest extends TestCase
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
     * Sube el archivo, relaciona las columnas (las sugeridas si no se indican) y confirma.
     *
     * @param  array<string, int|null>|null  $mapeo
     */
    private function importar(string $contenido, string $modo = 'crear', ?array $mapeo = null): Importacion
    {
        $this->post(route('gestion.importar.store', 'pucs'), [
            'archivo' => UploadedFile::fake()->createWithContent('pucs.csv', $contenido),
        ])->assertSessionHasNoErrors();

        $importacion = Importacion::latest('id')->firstOrFail();
        $sugerencia = $this->get(route('gestion.pucs.index'))->viewData('page')['props']['importacionPendiente']['sugerencia'];

        $this->post(route('gestion.importar.confirmar', $importacion), [
            'mapeo' => $mapeo ?? $sugerencia,
            'modo' => $modo,
        ])->assertSessionHasNoErrors();

        return $importacion->refresh();
    }

    private function cuenta(string $subcuenta, array $atributos = [], ?Tenant $tenant = null): Puc
    {
        return $this->crearEn(Puc::class, $tenant ?? $this->tenant, [
            'tipo' => '1',
            'cuenta' => substr($subcuenta, 0, 4),
            'subcuenta' => $subcuenta,
            'concepto' => 'Concepto '.$subcuenta,
            'concatenar_subcuenta_concepto' => "{$subcuenta} - Concepto {$subcuenta}",
            ...$atributos,
        ]);
    }

    public function test_importa_cuentas_y_arma_el_texto_concatenado(): void
    {
        $importacion = $this->importar("Clase;Cuenta;Subcuenta;Concepto;Descripción\n1;1105;110505;Caja general;Efectivo\n1;1110;111005;Bancos;\n");

        $this->assertSame(Importacion::COMPLETADA, $importacion->estado);
        $this->assertSame(2, $importacion->filas_creadas);
        $this->assertDatabaseHas('pucs', [
            'tenant_id' => $this->tenant->id,
            'subcuenta' => '110505',
            'concepto' => 'Caja general',
            'descripcion' => 'Efectivo',
            'concatenar_subcuenta_concepto' => '110505 - Caja general',
        ]);
    }

    public function test_acepta_comas_y_columnas_con_otros_nombres(): void
    {
        // "Tipo" y "Nombre" se sugieren solos como Clase y Concepto.
        $importacion = $this->importar("Tipo,Cuenta,Subcuenta,Nombre\n2,2205,220505,Proveedores\n");

        $this->assertSame(',', $importacion->delimitador);
        $this->assertDatabaseHas('pucs', ['subcuenta' => '220505', 'tipo' => '2', 'concepto' => 'Proveedores']);
    }

    public function test_relaciona_columnas_con_nombres_que_el_sistema_no_conoce(): void
    {
        $this->importar(
            "Cod;Grupo;Detalle;Clase contable\n110505;1105;Caja general;1\n",
            mapeo: ['tipo' => 3, 'cuenta' => 1, 'subcuenta' => 0, 'concepto' => 2, 'descripcion' => null],
        );

        $this->assertDatabaseHas('pucs', ['subcuenta' => '110505', 'cuenta' => '1105', 'tipo' => '1', 'concepto' => 'Caja general']);
    }

    public function test_rechaza_las_filas_invalidas_y_sigue_con_las_demas(): void
    {
        $importacion = $this->importar("Clase;Cuenta;Subcuenta;Concepto\n1;1105;110505;Caja\n0;1110;111005;Clase mala\n1;;112005;Sin cuenta\n1;1305;130505;Clientes\n");

        $this->assertSame(2, $importacion->filas_creadas);
        $this->assertSame(2, $importacion->filas_con_error);
        $this->assertSame([3, 4], array_column($importacion->errores, 'fila'));
        $this->assertSame(2, Puc::count());
    }

    public function test_importar_dos_veces_omite_las_subcuentas_existentes_y_respeta_las_de_otro_tenant(): void
    {
        $this->cuenta('110505', tenant: Tenant::factory()->create());
        $contenido = "Clase;Cuenta;Subcuenta;Concepto\n1;1105;110505;Caja\n";

        $this->importar($contenido);
        $segunda = $this->importar($contenido);

        $this->assertSame(1, Puc::count());
        $this->assertSame(1, $segunda->filas_omitidas);
        $this->assertSame(0, $segunda->filas_creadas);
    }

    public function test_exige_las_columnas_obligatorias_segun_el_modo(): void
    {
        $this->post(route('gestion.importar.store', 'pucs'), [
            'archivo' => UploadedFile::fake()->createWithContent('p.csv', "Subcuenta;Concepto\n110505;Caja\n"),
        ]);
        $importacion = Importacion::firstOrFail();

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['subcuenta' => 0, 'concepto' => 1], 'modo' => 'crear'])
            ->assertSessionHasErrors(['mapeo.tipo', 'mapeo.cuenta']);

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['subcuenta' => 0, 'concepto' => 1], 'modo' => 'actualizar'])
            ->assertSessionHasNoErrors();
    }

    public function test_actualizar_cambia_solo_lo_que_la_fila_trae_y_recalcula_el_texto(): void
    {
        $puc = $this->cuenta('110505', ['descripcion' => 'Se conserva']);

        $importacion = $this->importar("Subcuenta;Concepto;Descripción\n110505;Caja principal;\n", 'actualizar');

        $this->assertSame(1, $importacion->filas_actualizadas);
        $puc->refresh();
        $this->assertSame('Caja principal', $puc->concepto);
        $this->assertSame('Se conserva', $puc->descripcion, 'Una celda vacía no borra el dato.');
        $this->assertSame('110505 - Caja principal', $puc->concatenar_subcuenta_concepto);
        $this->assertSame('1', $puc->tipo);
    }

    public function test_actualizar_rechaza_subcuentas_inexistentes_o_de_otro_tenant_y_omite_lo_igual(): void
    {
        $this->cuenta('110505', ['concepto' => 'Caja']);
        $ajena = $this->cuenta('220505', tenant: Tenant::factory()->create());

        $importacion = $this->importar("Subcuenta;Concepto\n110505;Caja\n999999;Nada\n220505;Robada\n", 'actualizar');

        $this->assertSame(1, $importacion->filas_omitidas);
        $this->assertSame(2, $importacion->filas_con_error);
        $this->assertStringContainsString('No existe una cuenta', $importacion->errores[0]['mensaje']);
        $this->assertSame('Concepto 220505', $ajena->refresh()->concepto);
    }

    public function test_un_mismo_archivo_puede_crear_y_actualizar(): void
    {
        $existente = $this->cuenta('110505');

        $importacion = $this->importar(
            "Clase;Cuenta;Subcuenta;Concepto\n1;1105;110505;Caja general\n1;1110;111005;Bancos\n1;1305;130505\n",
            'ambos',
        );

        // La subcuenta que existe se actualiza; la que no, se crea; la incompleta se rechaza.
        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertSame(1, $importacion->filas_creadas);
        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertSame('Caja general', $existente->refresh()->concepto);
        $this->assertDatabaseHas('pucs', ['subcuenta' => '111005', 'concepto' => 'Bancos']);
        $this->assertDatabaseMissing('pucs', ['subcuenta' => '130505']);
    }

    public function test_exporta_el_plan_de_cuentas_del_tenant(): void
    {
        $this->cuenta('110505', ['descripcion' => 'Efectivo']);
        $this->cuenta('220505', ['tipo' => '2']);
        $this->cuenta('999999', tenant: Tenant::factory()->create());

        $csv = $this->get(route('gestion.exportar', ['modulo' => 'pucs', 'formato' => 'csv']));
        $csv->assertOk()->assertDownload();
        $contenido = $csv->streamedContent();

        $this->assertStringContainsString("Clase;Cuenta;Subcuenta;Concepto;Descripción\n", $contenido);
        $this->assertStringContainsString('1;1105;110505;"Concepto 110505";Efectivo', $contenido);
        $this->assertStringNotContainsString('999999', $contenido);

        $this->get(route('gestion.exportar', ['modulo' => 'pucs', 'formato' => 'xlsx']))->assertOk()->assertDownload();
        $this->get(route('gestion.exportar', ['modulo' => 'pucs', 'formato' => 'pdf']))->assertOk()->assertDownload();
    }

    public function test_un_archivo_exportado_se_puede_editar_y_reimportar_para_actualizar(): void
    {
        $puc = $this->cuenta('110505');

        $exportado = $this->get(route('gestion.exportar', ['modulo' => 'pucs', 'formato' => 'csv']))->streamedContent();
        $importacion = $this->importar(str_replace('Concepto 110505', 'Caja menor', $exportado), 'actualizar');

        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertSame('Caja menor', $puc->refresh()->concepto);
    }

    public function test_el_listado_trae_las_importaciones_y_la_configuracion_pendiente(): void
    {
        $this->importar("Clase;Cuenta;Subcuenta;Concepto\n1;1105;110505;Caja\n");

        $this->post(route('gestion.importar.store', 'pucs'), [
            'archivo' => UploadedFile::fake()->createWithContent('otro.csv', "Subcuenta;Nombre\n1;x\n"),
        ]);

        $this->get(route('gestion.pucs.index'))
            ->assertInertia(fn ($page) => $page
                ->has('importaciones.data', 1)
                ->where('importaciones.data.0.tipo', 'Plan de cuentas')
                ->where('importacionPendiente.etiqueta', 'Plan de cuentas')
                ->where('importacionPendiente.sugerencia.subcuenta', 0)
                ->where('importacionPendiente.sugerencia.concepto', 1));
    }

    public function test_importar_exige_crear_y_exportar_exige_ver(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['pucs.ver']));

        $this->get(route('gestion.exportar', ['modulo' => 'pucs', 'formato' => 'csv']))->assertOk();
        $this->post(route('gestion.importar.store', 'pucs'), [
            'archivo' => UploadedFile::fake()->createWithContent('p.csv', "Subcuenta\n1\n"),
        ])->assertForbidden();

        $this->actingAs($this->personalConPermisos($this->tenant, ['medidas.ver']));
        $this->get(route('gestion.exportar', ['modulo' => 'pucs', 'formato' => 'csv']))->assertForbidden();
    }
}
