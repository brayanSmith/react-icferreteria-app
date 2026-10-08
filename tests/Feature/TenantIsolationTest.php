<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoUsuarioDe(Tenant $tenant): User
    {
        $usuario = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($usuario);

        return $usuario;
    }

    /**
     * @return array<string, string>
     */
    private function datosCliente(string $numeroDocumento): array
    {
        return [
            'tipo_documento' => 'CC',
            'numero_documento' => $numeroDocumento,
            'razon_social' => 'Cliente '.$numeroDocumento,
        ];
    }

    public function test_un_usuario_solo_ve_los_registros_de_su_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $this->actuarComoUsuarioDe($tenantA);
        Cliente::create($this->datosCliente('111'));

        $this->actuarComoUsuarioDe($tenantB);
        Cliente::create($this->datosCliente('222'));

        $clientes = Cliente::all();

        $this->assertCount(1, $clientes);
        $this->assertSame('222', $clientes->first()->numero_documento);
        $this->assertSame($tenantB->id, $clientes->first()->tenant_id);
    }

    public function test_el_mismo_documento_puede_repetirse_en_tenants_distintos(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $this->actuarComoUsuarioDe($tenantA);
        Cliente::create($this->datosCliente('999'));

        $this->actuarComoUsuarioDe($tenantB);
        Cliente::create($this->datosCliente('999'));

        $this->assertSame(2, Cliente::withoutGlobalScopes()->where('numero_documento', '999')->count());
    }

    public function test_el_documento_no_puede_repetirse_dentro_del_mismo_tenant(): void
    {
        $this->actuarComoUsuarioDe(Tenant::factory()->create());
        Cliente::create($this->datosCliente('999'));

        $this->expectException(QueryException::class);

        Cliente::create($this->datosCliente('999'));
    }

    public function test_el_tenant_edita_los_datos_de_su_propia_empresa(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $this->actuarComoUsuarioDe($tenantA);
        Empresa::create(['nombre_empresa' => 'Ferreteria A']);

        $this->actuarComoUsuarioDe($tenantB);
        Empresa::create(['nombre_empresa' => 'Ferreteria B']);

        Empresa::query()->firstOrFail()->update(['nombre_empresa' => 'Ferreteria B Renovada']);

        $this->assertSame('Ferreteria B Renovada', Empresa::query()->firstOrFail()->nombre_empresa);
        $this->assertSame('Ferreteria A', Empresa::withoutGlobalScopes()->where('tenant_id', $tenantA->id)->firstOrFail()->nombre_empresa);
    }

    public function test_cada_tenant_tiene_una_sola_empresa(): void
    {
        $this->actuarComoUsuarioDe(Tenant::factory()->create());
        Empresa::create(['nombre_empresa' => 'Ferreteria A']);

        $this->expectException(QueryException::class);

        Empresa::create(['nombre_empresa' => 'Otra']);
    }

    public function test_el_super_admin_sin_tenant_ve_los_registros_de_todos(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $this->actuarComoUsuarioDe($tenantA);
        Cliente::create($this->datosCliente('111'));
        $this->actuarComoUsuarioDe($tenantB);
        Cliente::create($this->datosCliente('222'));

        $this->actingAs(User::factory()->create(['tenant_id' => null, 'es_super_admin' => true]));

        $this->assertCount(2, Cliente::all());
    }
}
