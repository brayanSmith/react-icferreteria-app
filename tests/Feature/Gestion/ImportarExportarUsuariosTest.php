<?php

namespace Tests\Feature\Gestion;

use App\Models\Importacion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportarExportarUsuariosTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->prepararGestion();
        $this->administrador = $this->administradorDe($this->tenant);
        $this->actingAs($this->administrador);
    }

    /**
     * Sube el archivo, relaciona las columnas (las sugeridas si no se indican) y confirma.
     */
    private function importar(string $contenido, string $modo = 'crear'): Importacion
    {
        $this->post(route('gestion.importar.store', 'usuarios'), [
            'archivo' => UploadedFile::fake()->createWithContent('usuarios.csv', $contenido),
        ])->assertSessionHasNoErrors();

        $importacion = Importacion::latest('id')->firstOrFail();
        $sugerencia = $this->get(route('gestion.usuarios.index'))->viewData('page')['props']['importacionPendiente']['sugerencia'];

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => $sugerencia, 'modo' => $modo])
            ->assertSessionHasNoErrors();

        return $importacion->refresh();
    }

    private function personal(string $correo, array $atributos = [], ?Tenant $tenant = null): User
    {
        return User::factory()->create([
            'tenant_id' => ($tenant ?? $this->tenant)->id,
            'email' => $correo,
            'es_admin' => false,
            ...$atributos,
        ]);
    }

    /**
     * @return list<string>
     */
    private function rolesDe(User $usuario): array
    {
        $this->usarTenantParaPermisos($this->tenant);

        return $usuario->refresh()->roles()->pluck('name')->sort()->values()->all();
    }

    public function test_crea_usuarios_con_roles_y_contrasena_temporal(): void
    {
        $this->crearRol($this->tenant, 'Vendedor');
        $this->crearRol($this->tenant, 'Bodeguero');

        $importacion = $this->importar(
            "Nombre;Correo;Documento;Contraseña temporal;Código de vendedor;Activo;Roles\n"
            ."Ana Ventas;Ana@Empresa.com;1032456789;ClaveTemporal#2026;V-01;Sí;vendedor\n"
            ."Luis Bodega;luis@empresa.com;80123456;ClaveTemporal#2026;;No;Bodeguero | Vendedor\n"
            ."Sin roles;sinroles@empresa.com;555;ClaveTemporal#2026;;;\n",
        );

        $this->assertSame(Importacion::COMPLETADA, $importacion->estado);
        $this->assertSame(3, $importacion->filas_creadas);

        $ana = User::where('email', 'ana@empresa.com')->firstOrFail();
        $this->assertSame($this->tenant->id, $ana->tenant_id);
        $this->assertFalse($ana->es_admin);
        $this->assertFalse($ana->es_super_admin);
        $this->assertTrue($ana->debe_cambiar_password);
        $this->assertNotNull($ana->email_verified_at);
        $this->assertTrue(Hash::check('ClaveTemporal#2026', $ana->password));
        $this->assertSame('V-01', $ana->cod_vendedor);
        $this->assertTrue((bool) $ana->activo);
        $this->assertSame(['Vendedor'], $this->rolesDe($ana));

        $luis = User::where('email', 'luis@empresa.com')->firstOrFail();
        $this->assertFalse((bool) $luis->activo);
        $this->assertSame(['Bodeguero', 'Vendedor'], $this->rolesDe($luis));

        $this->assertSame([], $this->rolesDe(User::where('email', 'sinroles@empresa.com')->firstOrFail()));
    }

    public function test_omite_al_personal_existente_y_rechaza_correos_o_documentos_en_uso(): void
    {
        $this->personal('existente@empresa.com', ['n_documento' => '111']);
        $this->personal('ajeno@otra.com', tenant: Tenant::factory()->create());
        $this->personal('borrado@empresa.com', ['n_documento' => '333'])->delete();
        $this->personal('doc@empresa.com', ['n_documento' => '444']);

        $importacion = $this->importar(
            "Nombre;Correo;Documento;Contraseña temporal\n"
            ."Existente;existente@empresa.com;111;ClaveTemporal#2026\n"
            ."Ajeno;ajeno@otra.com;222;ClaveTemporal#2026\n"
            ."Borrado;borrado@empresa.com;333;ClaveTemporal#2026\n"
            ."Admin;{$this->administrador->email};999;ClaveTemporal#2026\n"
            ."Doc repetido;nuevo@empresa.com;444;ClaveTemporal#2026\n",
        );

        $this->assertSame(0, $importacion->filas_creadas);
        $this->assertSame(1, $importacion->filas_omitidas);
        $this->assertSame(4, $importacion->filas_con_error);
        $this->assertStringContainsString('ya está en uso', $importacion->errores[0]['mensaje']);
        $this->assertStringContainsString('ya lo tiene otro usuario', $importacion->errores[3]['mensaje']);
        $this->assertDatabaseMissing('users', ['email' => 'nuevo@empresa.com']);
    }

    public function test_rechaza_contrasenas_debiles_roles_inexistentes_y_datos_invalidos(): void
    {
        $this->crearRol($this->tenant, 'Vendedor');
        $rolAjeno = $this->crearRol(Tenant::factory()->create(), 'Rol ajeno');

        $importacion = $this->importar(
            "Nombre;Correo;Documento;Contraseña temporal;Roles\n"
            ."Buena;buena@empresa.com;1;ClaveTemporal#2026;Vendedor\n"
            ."Clave corta;corta@empresa.com;2;abc;\n"
            ."Sin clave;sinclave@empresa.com;3;;\n"
            ."Rol fantasma;fantasma@empresa.com;4;ClaveTemporal#2026;Inventado\n"
            ."Rol ajeno;rolajeno@empresa.com;5;ClaveTemporal#2026;{$rolAjeno->name}\n"
            ."Correo malo;no-es-correo;6;ClaveTemporal#2026;\n"
            .";sinnombre@empresa.com;7;ClaveTemporal#2026;\n",
        );

        $this->assertSame(1, $importacion->filas_creadas);
        $this->assertSame(6, $importacion->filas_con_error);
        $this->assertStringContainsString('Contraseña temporal debe tener al menos', $importacion->errores[0]['mensaje']);
        $this->assertStringContainsString('Falta Contraseña temporal', $importacion->errores[1]['mensaje']);
        $this->assertStringContainsString('El rol Inventado no existe', $importacion->errores[2]['mensaje']);
        $this->assertStringContainsString('El rol Rol ajeno no existe', $importacion->errores[3]['mensaje']);
        $this->assertStringContainsString('Correo no es un correo válido', $importacion->errores[4]['mensaje']);
        $this->assertStringContainsString('Falta Nombre', $importacion->errores[5]['mensaje']);
        $this->assertSame(1 + 1, User::where('tenant_id', $this->tenant->id)->count(), 'Solo el administrador y la fila buena.');
    }

    public function test_exige_las_columnas_obligatorias_segun_el_modo(): void
    {
        $this->post(route('gestion.importar.store', 'usuarios'), [
            'archivo' => UploadedFile::fake()->createWithContent('u.csv', "Nombre;Correo\nAna;ana@empresa.com\n"),
        ]);
        $importacion = Importacion::firstOrFail();

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['name' => 0, 'email' => 1], 'modo' => 'crear'])
            ->assertSessionHasErrors(['mapeo.n_documento', 'mapeo.password']);

        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['name' => 0, 'email' => 1], 'modo' => 'actualizar'])
            ->assertSessionHasNoErrors();
    }

    public function test_actualizar_cambia_solo_lo_que_la_fila_trae_y_reemplaza_los_roles(): void
    {
        $this->crearRol($this->tenant, 'Vendedor');
        $this->crearRol($this->tenant, 'Bodeguero');
        $usuario = $this->personal('ana@empresa.com', ['name' => 'Ana', 'n_documento' => '1', 'cod_vendedor' => 'V-01', 'activo' => true]);
        $this->usarTenantParaPermisos($this->tenant);
        $usuario->assignRole('Vendedor');

        $importacion = $this->importar("Correo;Nombre;Código de vendedor;Activo;Roles\nana@empresa.com;Ana María;;No;Bodeguero\n", 'actualizar');

        $this->assertSame(1, $importacion->filas_actualizadas);
        $usuario->refresh();
        $this->assertSame('Ana María', $usuario->name);
        $this->assertSame('V-01', $usuario->cod_vendedor, 'Una celda vacía no borra el dato.');
        $this->assertFalse((bool) $usuario->activo);
        $this->assertSame('1', $usuario->n_documento);
        $this->assertSame(['Bodeguero'], $this->rolesDe($usuario));
    }

    public function test_actualizar_con_contrasena_la_restablece_como_temporal(): void
    {
        $usuario = $this->personal('ana@empresa.com', ['debe_cambiar_password' => false]);
        $anterior = $usuario->password;

        $this->importar("Correo;Contraseña temporal\nana@empresa.com;NuevaClave#2026\n", 'actualizar');

        $usuario->refresh();
        $this->assertNotSame($anterior, $usuario->password);
        $this->assertTrue(Hash::check('NuevaClave#2026', $usuario->password));
        $this->assertTrue($usuario->debe_cambiar_password);
    }

    public function test_actualizar_omite_lo_igual_y_conserva_los_roles_si_la_columna_no_viene(): void
    {
        $this->crearRol($this->tenant, 'Vendedor');
        $usuario = $this->personal('ana@empresa.com', ['name' => 'Ana', 'activo' => true]);
        $this->usarTenantParaPermisos($this->tenant);
        $usuario->assignRole('Vendedor');

        $importacion = $this->importar("Correo;Nombre;Activo\nana@empresa.com;Ana;Sí\n", 'actualizar');

        $this->assertSame(1, $importacion->filas_omitidas);
        $this->assertSame(0, $importacion->filas_actualizadas);
        $this->assertSame(['Vendedor'], $this->rolesDe($usuario));
    }

    public function test_actualizar_no_toca_administradores_otros_tenants_ni_al_propio_usuario(): void
    {
        $otroAdmin = $this->administradorDe($this->tenant);
        $ajeno = $this->personal('ajeno@otra.com', ['name' => 'Ajeno'], Tenant::factory()->create());
        $otroNombre = $otroAdmin->name;

        $importacion = $this->importar(
            "Correo;Nombre\n{$otroAdmin->email};Hackeado\najeno@otra.com;Hackeado\n{$this->administrador->email};Hackeado\nnadie@empresa.com;Hackeado\n",
            'actualizar',
        );

        $this->assertSame(4, $importacion->filas_con_error);
        $this->assertSame($otroNombre, $otroAdmin->refresh()->name);
        $this->assertSame('Ajeno', $ajeno->refresh()->name);
        $this->assertNotSame('Hackeado', $this->administrador->refresh()->name);
    }

    public function test_el_personal_no_puede_modificarse_a_si_mismo_por_importacion(): void
    {
        $this->crearRol($this->tenant, 'Gestor de usuarios');
        $gestor = $this->personalConPermisos($this->tenant, ['usuarios.ver', 'usuarios.crear', 'usuarios.editar']);
        $this->actingAs($gestor);

        $importacion = $this->importar("Correo;Roles\n{$gestor->email};Gestor de usuarios\n", 'actualizar');

        $this->assertSame(1, $importacion->filas_con_error);
        $this->assertStringContainsString('No puedes modificar tu propio usuario', $importacion->errores[0]['mensaje']);
    }

    public function test_un_mismo_archivo_puede_crear_y_actualizar(): void
    {
        $existente = $this->personal('ana@empresa.com', ['name' => 'Ana']);

        $importacion = $this->importar(
            "Nombre;Correo;Documento;Contraseña temporal\nAna Renombrada;ana@empresa.com;;\nNuevo;nuevo@empresa.com;777;ClaveTemporal#2026\n",
            'ambos',
        );

        $this->assertSame(1, $importacion->filas_actualizadas);
        $this->assertSame(1, $importacion->filas_creadas);
        $this->assertSame('Ana Renombrada', $existente->refresh()->name);
        $this->assertDatabaseHas('users', ['email' => 'nuevo@empresa.com', 'tenant_id' => $this->tenant->id]);
    }

    public function test_exporta_solo_el_personal_del_tenant_y_nunca_contrasenas(): void
    {
        $this->crearRol($this->tenant, 'Vendedor');
        $this->crearRol($this->tenant, 'Bodeguero');
        $ana = $this->personal('ana@empresa.com', ['name' => 'Ana', 'cod_vendedor' => 'V-01']);
        $this->usarTenantParaPermisos($this->tenant);
        $ana->assignRole('Vendedor', 'Bodeguero');
        $this->personal('ajeno@otra.com', tenant: Tenant::factory()->create());

        $respuesta = $this->get(route('gestion.exportar', ['modulo' => 'usuarios', 'formato' => 'csv']));
        $respuesta->assertOk()->assertDownload();
        $contenido = $respuesta->streamedContent();

        $this->assertStringContainsString('Nombre;Correo;Documento;"Código de vendedor";Activo;Roles', $contenido);
        $this->assertStringContainsString('ana@empresa.com', $contenido);
        $this->assertStringContainsString('"Bodeguero | Vendedor"', $contenido);
        $this->assertStringNotContainsString('ajeno@otra.com', $contenido);
        $this->assertStringNotContainsString($this->administrador->email, $contenido, 'Los administradores no se exportan.');
        $this->assertStringNotContainsString($ana->password, $contenido);
        $this->assertStringNotContainsStringIgnoringCase('contraseña', $contenido);

        $this->get(route('gestion.exportar', ['modulo' => 'usuarios', 'formato' => 'xlsx']))->assertOk()->assertDownload();
        $this->get(route('gestion.exportar', ['modulo' => 'usuarios', 'formato' => 'pdf']))->assertOk()->assertDownload();
    }

    public function test_un_archivo_exportado_se_puede_editar_y_reimportar_sin_alterar_los_roles(): void
    {
        $this->crearRol($this->tenant, 'Vendedor');
        $ana = $this->personal('ana@empresa.com', ['name' => 'Ana']);
        $this->usarTenantParaPermisos($this->tenant);
        $ana->assignRole('Vendedor');
        $contrasena = $ana->password;

        $exportado = $this->get(route('gestion.exportar', ['modulo' => 'usuarios', 'formato' => 'csv']))->streamedContent();
        $importacion = $this->importar(str_replace('Ana', 'Ana Editada', $exportado), 'actualizar');

        $this->assertSame(0, $importacion->filas_con_error);
        $this->assertSame(1, $importacion->filas_actualizadas);
        $ana->refresh();
        $this->assertSame('Ana Editada', $ana->name);
        $this->assertSame($contrasena, $ana->password, 'Sin columna de contraseña, la clave no cambia.');
        $this->assertSame(['Vendedor'], $this->rolesDe($ana));
    }

    public function test_importar_exige_crear_actualizar_exige_editar_y_exportar_exige_ver(): void
    {
        $soloCrear = $this->personalConPermisos($this->tenant, ['usuarios.ver', 'usuarios.crear']);
        $this->actingAs($soloCrear);

        $this->post(route('gestion.importar.store', 'usuarios'), [
            'archivo' => UploadedFile::fake()->createWithContent('u.csv', "Correo\nana@empresa.com\n"),
        ])->assertSessionHasNoErrors();
        $importacion = Importacion::firstOrFail();

        // Con solo "crear" no se puede modificar usuarios existentes por importación.
        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['email' => 0], 'modo' => 'actualizar'])->assertForbidden();
        $this->post(route('gestion.importar.confirmar', $importacion), ['mapeo' => ['email' => 0], 'modo' => 'ambos'])->assertForbidden();

        $this->actingAs($this->personalConPermisos($this->tenant, ['usuarios.ver']));
        $this->get(route('gestion.exportar', ['modulo' => 'usuarios', 'formato' => 'csv']))->assertOk();
        $this->post(route('gestion.importar.store', 'usuarios'), [
            'archivo' => UploadedFile::fake()->createWithContent('u.csv', "Correo\nana@empresa.com\n"),
        ])->assertForbidden();

        $this->actingAs($this->personalConPermisos($this->tenant, ['medidas.ver']));
        $this->get(route('gestion.exportar', ['modulo' => 'usuarios', 'formato' => 'csv']))->assertForbidden();
    }
}
