<?php

namespace Tests\Feature\Gestion;

use App\Models\Cliente;
use App\Models\Importacion;
use App\Models\Ruta;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportarExportarClientesTest extends TestCase
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
        $this->post(route('gestion.importar.store', 'clientes'), [
            'archivo' => UploadedFile::fake()->createWithContent('clientes.csv', $contenido),
        ])->assertSessionHasNoErrors();

        $importacion = Importacion::latest('id')->firstOrFail();
        $sugerencia = $this->get(route('gestion.clientes.index'))->viewData('page')['props']['importacionPendiente']['sugerencia'];

        $this->post(route('gestion.importar.confirmar', $importacion), [
            'mapeo' => $mapeo ?? $sugerencia,
            'modo' => $modo,
        ])->assertSessionHasNoErrors();

        return $importacion->refresh();
    }

    private function cliente(string $documento, array $atributos = [], ?Tenant $tenant = null): Cliente
    {
        return $this->crearEn(Cliente::class, $tenant ?? $this->tenant, [
            'tipo_documento' => 'NIT',
            'numero_documento' => $documento,
            'razon_social' => 'Cliente '.$documento,
            ...$atributos,
        ]);
    }

    public function test_crea_clientes_y_relaciona_ruta_y_comercial_por_nombre(): void
    {
        $ruta = $this->crearEn(Ruta::class, $this->tenant, ['ruta' => 'Norte']);
        $comercial = User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Ana Ventas', 'email' => 'ana@empresa.com']);

        $importacion = $this->importar(
            "Tipo de documento;Número de documento;Razón social;Correo;Ruta;Comercial;Tipo de cliente;Retenedor de fuente;Límite de crédito;Activo\n"
            ."NIT;900123456;Ferretería El Tornillo;compras@eltornillo.com;norte;Ana Ventas;Remisionado;Sí;1.500.000,50;No\n"
            ."Cédula de ciudadanía;1032456789;Juan Pérez;;;ana@empresa.com;;;;\n",
        );

        $this->assertSame(Importacion::COMPLETADA, $importacion->estado);
        $this->assertSame(2, $importacion->filas_creadas);

        $primero = Cliente::where('numero_documento', '900123456')->firstOrFail();
        $this->assertSame($this->tenant->id, $primero->tenant_id);
        $this->assertSame($ruta->id, $primero->ruta_id);
        $this->assertSame($comercial->id, $primero->comercial_id);
        $this->assertSame('REMISIONADO', $primero->tipo_cliente);
        $this->assertSame('SI', $primero->retenedor_fuente);
        $this->assertEquals(1500000.5, $primero->limite_credito);
        $this->assertFalse((bool) $primero->activo);

        $segundo = Cliente::where('numero_documento', '1032456789')->firstOrFail();
        $this->assertSame('CC', $segundo->tipo_documento);
        $this->assertSame($comercial->id, $segundo->comercial_id);
        $this->assertTrue((bool) $segundo->activo);
        $this->assertSame('ELECTRONICO', $segundo->tipo_cliente);
        $this->assertSame('NO', $segundo->retenedor_fuente);
    }

    public function test_importar_dos_veces_omite_los_documentos_existentes(): void
    {
        $contenido = "Tipo de documento;Número de documento;Razón social\nNIT;900123456;Ferretería\n";

        $this->importar($contenido);
        $segunda = $this->importar($contenido);

        $this->assertSame(1, Cliente::count());
        $this->assertSame(1, $segunda->filas_omitidas);
        $this->assertSame(0, $segunda->filas_creadas);
    }

    public function test_el_mismo_documento_puede_existir_en_otro_tenant(): void
    {
        $this->cliente('900123456', tenant: Tenant::factory()->create());

        $importacion = $this->importar("Tipo de documento;Número de documento;Razón social\nNIT;900123456;Ferretería\n");

        $this->assertSame(1, $importacion->filas_creadas);
        $this->assertSame(1, Cliente::count());
    }

    public function test_rechaza_filas_invalidas_correos_repetidos_y_catalogos_inexistentes(): void
    {
        $this->cliente('111', ['email' => 'usado@correo.com']);

        $importacion = $this->importar(
            "Tipo de documento;Número de documento;Razón social;Correo;Ruta;Comercial\n"
            ."NIT;200;Buena;;;\n"
            ."XXX;201;TipoMalo;;;\n"
            ."NIT;202;;;;\n"
            ."NIT;203;Correo malo;no-es-correo;;\n"
            ."NIT;204;Correo usado;usado@correo.com;;\n"
            ."NIT;205;Ruta fantasma;;Fantasma;\n"
            ."NIT;206;Comercial fantasma;;;Nadie\n",
        );

        $this->assertSame(1, $importacion->filas_creadas);
        $this->assertSame(6, $importacion->filas_con_error);
        $this->assertSame([3, 4, 5, 6, 7, 8], array_column($importacion->errores, 'fila'));
        $this->assertStringContainsString('Tipo de documento', $importacion->errores[0]['mensaje']);
        $this->assertStringContainsString('Falta Razón social', $importacion->errores[1]['mensaje']);
        $this->assertStringContainsString('ya lo usa otro cliente', $importacion->errores[3]['mensaje']);
        $this->assertStringContainsString('La ruta Fantasma no existe', $importacion->errores[4]['mensaje']);
        $this->assertStringContainsString('Nadie', $importacion->errores[5]['mensaje']);
        $this->assertSame(2, Cliente::count());
    }

    public function test_no_asigna_rutas_ni_comerciales_de_otro_tenant(): void
    {
        $this->crearEn(Ruta::class, Tenant::factory()->create(), ['ruta' => 'Ajena']);
        User::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'name' => 'Persona Ajena']);

        $importacion = $this->importar(
            "Tipo de documento;Número de documento;Razón social;Ruta;Comercial\nNIT;1;Uno;Ajena;\nNIT;2;Dos;;Persona Ajena\n",
        );

        $this->assertSame(2, $importacion->filas_con_error);
        $this->assertSame(0, Cliente::count());
    }

    public function test_un_cliente_eliminado_con_el_mismo_documento_se_rechaza_y_no_se_duplica(): void
    {
        $this->cliente('900123456')->delete();

        $importacion = $this->importar("Tipo de documento;Número de documento;Razón social\nNIT;900123456;Ferretería\n");

        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertStringContainsString('está eliminado', $importacion->errores[0]['mensaje']);
        $this->assertSame(0, Cliente::count());
    }

    public function test_relaciona_columnas_con_nombres_que_el_sistema_no_conoce(): void
    {
        $this->importar(
            "Cod;Tipo id;Empresa;Mail\n900123456;NIT;Ferretería;f@correo.com\n",
            mapeo: ['numero_documento' => 0, 'tipo_documento' => 1, 'razon_social' => 2, 'email' => 3],
        );

        $this->assertDatabaseHas('clientes', ['numero_documento' => '900123456', 'razon_social' => 'Ferretería', 'email' => 'f@correo.com']);
    }

    public function test_acepta_comas_y_sugiere_columnas_por_sinonimos(): void
    {
        $importacion = $this->importar("Tipo documento,NIT,Nombre,Celular\nNIT,900123456,Ferretería,3001234567\n");

        $this->assertSame(',', $importacion->delimitador);
        $this->assertDatabaseHas('clientes', ['numero_documento' => '900123456', 'razon_social' => 'Ferretería', 'telefono' => '3001234567']);
    }

    public function test_exige_las_columnas_obligatorias_segun_el_modo(): void
    {
        $this->post(route('gestion.importar.store', 'clientes'), [
            'archivo' => UploadedFile::fake()->createWithContent('c.csv', "Documento;Nombre\n1;Uno\n"),
        ]);
        $importacion = Importacion::firstOrFail();

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['numero_documento' => 0, 'razon_social' => 1], 'modo' => 'crear'])
            ->assertSessionHasErrors('mapeo.tipo_documento');

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['numero_documento' => 0, 'razon_social' => 1], 'modo' => 'actualizar'])
            ->assertSessionHasNoErrors();
    }

    public function test_actualizar_cambia_solo_lo_que_la_fila_trae_y_conserva_el_documento(): void
    {
        $cliente = $this->cliente('900123456', ['telefono' => '300', 'ciudad' => 'Bogotá', 'limite_credito' => 100]);

        $importacion = $this->importar("Número de documento;Razón social;Teléfono;Ciudad;Límite de crédito\n900123456;Nueva razón;;;100\n", 'actualizar');

        $this->assertSame(1, $importacion->filas_actualizadas);
        $cliente->refresh();
        $this->assertSame('900123456', $cliente->numero_documento);
        $this->assertSame('Nueva razón', $cliente->razon_social);
        $this->assertSame('300', $cliente->telefono, 'Una celda vacía no borra el dato.');
        $this->assertSame('Bogotá', $cliente->ciudad);
    }

    public function test_actualizar_rechaza_documentos_inexistentes_ajenos_eliminados_y_correos_en_uso_y_omite_lo_igual(): void
    {
        $this->cliente('1', ['email' => 'uno@correo.com', 'telefono' => '300']);
        $this->cliente('2', ['email' => 'dos@correo.com']);
        $this->cliente('3')->delete();
        $ajeno = $this->cliente('4', tenant: Tenant::factory()->create());

        $importacion = $this->importar(
            "Número de documento;Teléfono;Correo\n1;300;\n999;1;\n4;1;\n3;1;\n2;;uno@correo.com\n",
            'actualizar',
        );

        $this->assertSame(1, $importacion->filas_omitidas);
        $this->assertSame(4, $importacion->filas_con_error);
        $this->assertStringContainsString('No existe un cliente', $importacion->errores[0]['mensaje']);
        $this->assertStringContainsString('No existe un cliente', $importacion->errores[1]['mensaje']);
        $this->assertStringContainsString('está eliminado', $importacion->errores[2]['mensaje']);
        $this->assertStringContainsString('ya lo usa otro cliente', $importacion->errores[3]['mensaje']);
        $this->assertNull($ajeno->refresh()->telefono);
    }

    public function test_un_mismo_archivo_puede_crear_y_actualizar(): void
    {
        $existente = $this->cliente('900123456');

        $importacion = $this->importar(
            "Tipo de documento;Número de documento;Razón social\nNIT;900123456;Renombrado\nCC;777;Nuevo cliente\nNIT;888;\n",
            'ambos',
        );

        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertSame(1, $importacion->filas_creadas);
        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertSame('Renombrado', $existente->refresh()->razon_social);
        $this->assertDatabaseHas('clientes', ['numero_documento' => '777', 'razon_social' => 'Nuevo cliente']);
    }

    public function test_exporta_los_clientes_del_tenant_con_nombres_legibles(): void
    {
        $ruta = $this->crearEn(Ruta::class, $this->tenant, ['ruta' => 'Norte']);
        $this->cliente('900123456', ['tipo_cliente' => 'REMISIONADO', 'ruta_id' => $ruta->id, 'retenedor_fuente' => 'SI', 'activo' => false]);
        $this->cliente('777', tenant: Tenant::factory()->create());

        $respuesta = $this->get(route('gestion.exportar', ['modulo' => 'clientes', 'formato' => 'csv']));
        $respuesta->assertOk()->assertDownload();
        $contenido = $respuesta->streamedContent();

        $this->assertStringContainsString('"Tipo de documento";"Número de documento"', $contenido);
        $this->assertStringContainsString('NIT;900123456;"Cliente 900123456"', $contenido);
        $this->assertStringContainsString('Norte', $contenido);
        $this->assertStringContainsString('Remisionado;Sí;', $contenido);
        $this->assertStringNotContainsString('777', $contenido);

        $this->get(route('gestion.exportar', ['modulo' => 'clientes', 'formato' => 'xlsx']))->assertOk()->assertDownload();
        $this->get(route('gestion.exportar', ['modulo' => 'clientes', 'formato' => 'pdf']))->assertOk()->assertDownload();
    }

    public function test_un_archivo_exportado_se_puede_editar_y_reimportar_para_actualizar(): void
    {
        $ruta = $this->crearEn(Ruta::class, $this->tenant, ['ruta' => 'Norte']);
        $cliente = $this->cliente('900123456', ['ruta_id' => $ruta->id, 'tipo_cliente' => 'REMISIONADO', 'retenedor_fuente' => 'SI']);

        $exportado = $this->get(route('gestion.exportar', ['modulo' => 'clientes', 'formato' => 'csv']))->streamedContent();
        $importacion = $this->importar(str_replace('Cliente 900123456', 'Editado en Excel', $exportado), 'actualizar');

        $this->assertSame(0, $importacion->filas_con_error);
        $this->assertSame(1, $importacion->filas_actualizadas);
        $cliente->refresh();
        $this->assertSame('Editado en Excel', $cliente->razon_social);
        $this->assertSame('REMISIONADO', $cliente->tipo_cliente);
        $this->assertSame('SI', $cliente->retenedor_fuente);
        $this->assertSame($ruta->id, $cliente->ruta_id);
    }

    public function test_importar_exige_crear_y_exportar_exige_ver(): void
    {
        $this->actingAs($this->personalConPermisos($this->tenant, ['clientes.ver']));

        $this->get(route('gestion.exportar', ['modulo' => 'clientes', 'formato' => 'csv']))->assertOk();
        $this->post(route('gestion.importar.store', 'clientes'), [
            'archivo' => UploadedFile::fake()->createWithContent('c.csv', "Documento\n1\n"),
        ])->assertForbidden();

        $this->actingAs($this->personalConPermisos($this->tenant, ['medidas.ver']));
        $this->get(route('gestion.exportar', ['modulo' => 'clientes', 'formato' => 'csv']))->assertForbidden();
    }
}
