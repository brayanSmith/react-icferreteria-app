<?php

namespace Tests\Feature\Gestion;

use App\Models\Empresa;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmpresaManagementTest extends TestCase
{
    use PreparaGestion;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        // El HTML de la primera carga se prueba sin SSR, para no depender de que haya un servidor de Vite activo.
        config(['inertia.ssr.enabled' => false]);
        $this->prepararGestion();
        $this->actingAs($this->administradorDe($this->tenant));
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function datos(array $sobrescribir = []): array
    {
        return [
            'nombre_empresa' => 'Ferretería El Tornillo',
            'nit_empresa' => '900123456-7',
            'email_empresa' => 'contacto@tornillo.test',
            'telefono_empresa' => '3001234567',
            'direccion_empresa' => 'Calle 1 # 2-3',
            'mostrar_productos_sin_inventario' => '1',
            ...$sobrescribir,
        ];
    }

    public function test_sin_empresa_la_pantalla_sugiere_el_nombre_del_tenant(): void
    {
        $this->get(route('gestion.empresa.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gestion/empresa/edit')
                ->where('empresa', null)
                ->where('nombreSugerido', $this->tenant->nombre));
    }

    public function test_el_primer_guardado_crea_la_empresa_del_tenant(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos())
            ->assertRedirect(route('gestion.empresa.edit'));

        $this->assertDatabaseHas('empresas', [
            'tenant_id' => $this->tenant->id,
            'nombre_empresa' => 'Ferretería El Tornillo',
            'nit_empresa' => '900123456-7',
        ]);
    }

    public function test_guardar_de_nuevo_actualiza_sin_duplicar_la_empresa(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos());
        $this->put(route('gestion.empresa.update'), $this->datos(['nombre_empresa' => 'Nuevo Nombre']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Empresa::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count());
        $this->assertDatabaseHas('empresas', ['tenant_id' => $this->tenant->id, 'nombre_empresa' => 'Nuevo Nombre']);
    }

    public function test_cada_tenant_ve_y_edita_solo_su_empresa(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos(['nombre_empresa' => 'Empresa A']));

        $otroTenant = Tenant::factory()->create();
        $this->actingAs($this->administradorDe($otroTenant));

        $this->get(route('gestion.empresa.edit'))->assertInertia(fn ($page) => $page->where('empresa', null));

        $this->put(route('gestion.empresa.update'), $this->datos(['nombre_empresa' => 'Empresa B']));

        $this->assertDatabaseHas('empresas', ['tenant_id' => $this->tenant->id, 'nombre_empresa' => 'Empresa A']);
        $this->assertDatabaseHas('empresas', ['tenant_id' => $otroTenant->id, 'nombre_empresa' => 'Empresa B']);
    }

    public function test_guarda_las_cuentas_bancarias(): void
    {
        $cuentas = [
            ['banco' => 'Bancolombia', 'tipo' => 'AHORRO', 'numero' => '123-456', 'titular' => 'El Tornillo SAS'],
            ['banco' => 'Davivienda', 'tipo' => 'CORRIENTE', 'numero' => '789', 'titular' => ''],
        ];

        $this->put(route('gestion.empresa.update'), $this->datos(['cuentas_bancarias' => $cuentas]))
            ->assertSessionHasNoErrors();

        $this->get(route('gestion.empresa.edit'))
            ->assertInertia(fn ($page) => $page
                ->has('empresa.data.cuentas_bancarias', 2)
                ->where('empresa.data.cuentas_bancarias.0.banco', 'Bancolombia'));
    }

    public function test_valida_los_datos_de_la_empresa_y_de_las_cuentas(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos([
            'nombre_empresa' => '',
            'email_empresa' => 'no-es-correo',
            'cuentas_bancarias' => [['banco' => '', 'tipo' => 'OTRO', 'numero' => '']],
        ]))->assertSessionHasErrors([
            'nombre_empresa',
            'email_empresa',
            'cuentas_bancarias.0.banco',
            'cuentas_bancarias.0.tipo',
            'cuentas_bancarias.0.numero',
        ]);

        $this->assertDatabaseCount('empresas', 0);
    }

    public function test_sube_el_logo_y_el_icono(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos([
            'logo' => UploadedFile::fake()->image('logo.png'),
            'icono' => UploadedFile::fake()->image('icono.png', 64, 64),
        ]))->assertSessionHasNoErrors();

        $empresa = Empresa::firstOrFail();

        Storage::disk('public')->assertExists($empresa->logo_empresa);
        Storage::disk('public')->assertExists($empresa->icono_empresa);
        $this->assertStringStartsWith("empresas/{$this->tenant->id}/", $empresa->logo_empresa);
    }

    public function test_rechaza_archivos_que_no_son_imagenes(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos([
            'logo' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors('logo');
    }

    public function test_reemplazar_el_logo_borra_el_archivo_anterior(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos(['logo' => UploadedFile::fake()->image('uno.png')]));
        $anterior = Empresa::firstOrFail()->logo_empresa;

        $this->put(route('gestion.empresa.update'), $this->datos(['logo' => UploadedFile::fake()->image('dos.png')]));
        $nuevo = Empresa::firstOrFail()->logo_empresa;

        $this->assertNotSame($anterior, $nuevo);
        Storage::disk('public')->assertMissing($anterior);
        Storage::disk('public')->assertExists($nuevo);
    }

    public function test_conserva_las_imagenes_si_no_se_envian_nuevas(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos(['logo' => UploadedFile::fake()->image('logo.png')]));
        $logo = Empresa::firstOrFail()->logo_empresa;

        $this->put(route('gestion.empresa.update'), $this->datos(['nombre_empresa' => 'Otro Nombre']));

        $this->assertSame($logo, Empresa::firstOrFail()->logo_empresa);
        Storage::disk('public')->assertExists($logo);
    }

    public function test_puede_quitar_el_logo(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos(['logo' => UploadedFile::fake()->image('logo.png')]));
        $logo = Empresa::firstOrFail()->logo_empresa;

        $this->put(route('gestion.empresa.update'), $this->datos(['quitar_logo' => '1']));

        $this->assertNull(Empresa::firstOrFail()->logo_empresa);
        Storage::disk('public')->assertMissing($logo);
    }

    public function test_comparte_la_marca_de_la_empresa_en_todas_las_paginas(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos([
            'logo' => UploadedFile::fake()->image('logo.png'),
            'icono' => UploadedFile::fake()->image('icono.png', 64, 64),
        ]));
        $empresa = Empresa::firstOrFail();

        $this->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('marca.nombre', 'Ferretería El Tornillo')
                ->where('marca.logo_url', $empresa->logoUrl())
                ->where('marca.icono_url', $empresa->iconoUrl()));
    }

    public function test_sin_empresa_registrada_no_se_comparte_marca(): void
    {
        $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->where('marca', null));
    }

    public function test_cada_tenant_recibe_solo_su_propia_marca(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos(['nombre_empresa' => 'Empresa A']));

        $this->actingAs($this->administradorDe(Tenant::factory()->create()));
        $this->put(route('gestion.empresa.update'), $this->datos(['nombre_empresa' => 'Empresa B']));

        $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->where('marca.nombre', 'Empresa B'));
    }

    public function test_el_super_admin_no_recibe_la_marca_de_ninguna_empresa(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos());

        $this->actingAs(User::factory()->create(['tenant_id' => null, 'es_super_admin' => true]));

        $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->where('marca', null));
    }

    public function test_la_marca_tambien_llega_en_la_propia_pagina_de_la_empresa(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos([
            'icono' => UploadedFile::fake()->image('icono.png', 64, 64),
        ]));
        $iconoUrl = Empresa::firstOrFail()->iconoUrl();

        // La página envía su propia propiedad "empresa"; no debe pisar a la marca compartida.
        $this->get(route('gestion.empresa.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('marca.nombre', 'Ferretería El Tornillo')
                ->where('marca.icono_url', $iconoUrl)
                ->where('empresa.data.nombre_empresa', 'Ferretería El Tornillo'))
            ->assertSee('<title>Ferretería El Tornillo</title>', false)
            ->assertSee('<link rel="icon" href="'.$iconoUrl.'">', false);
    }

    public function test_la_primera_carga_usa_el_nombre_y_el_icono_de_la_empresa(): void
    {
        $this->put(route('gestion.empresa.update'), $this->datos([
            'icono' => UploadedFile::fake()->image('icono.png', 64, 64),
        ]));
        $iconoUrl = Empresa::firstOrFail()->iconoUrl();

        $this->get(route('dashboard'))
            ->assertSee('<title>Ferretería El Tornillo</title>', false)
            ->assertSee('<link rel="icon" href="'.$iconoUrl.'">', false)
            ->assertDontSee('/favicon.svg');
    }

    public function test_sin_empresa_la_primera_carga_usa_el_favicon_predeterminado(): void
    {
        $this->get(route('dashboard'))
            ->assertSee('/favicon.svg')
            ->assertSee('<title>'.config('app.name').'</title>', false);
    }

    public function test_ver_y_editar_son_permisos_distintos(): void
    {
        $soloVer = $this->personalConPermisos($this->tenant, ['empresa.ver']);
        $this->actingAs($soloVer);

        $this->get(route('gestion.empresa.edit'))->assertOk();
        $this->put(route('gestion.empresa.update'), $this->datos())->assertForbidden();

        $editor = $this->personalConPermisos($this->tenant, ['empresa.editar']);
        $this->actingAs($editor);

        $this->get(route('gestion.empresa.edit'))->assertForbidden();
        $this->put(route('gestion.empresa.update'), $this->datos())->assertRedirect();
    }

    public function test_el_personal_sin_permisos_y_el_super_admin_no_entran(): void
    {
        $this->actingAs(User::factory()->create(['tenant_id' => $this->tenant->id]));
        $this->get(route('gestion.empresa.edit'))->assertForbidden();

        $this->actingAs(User::factory()->create(['tenant_id' => null, 'es_super_admin' => true]));
        $this->get(route('gestion.empresa.edit'))->assertForbidden();
    }
}
