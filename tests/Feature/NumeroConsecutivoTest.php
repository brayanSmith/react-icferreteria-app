<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Consecutivo;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NumeroConsecutivoTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoUsuarioDe(Tenant $tenant): void
    {
        $this->actingAs(User::factory()->create(['tenant_id' => $tenant->id]));
    }

    private function crearCliente(string $documento): Cliente
    {
        return Cliente::create([
            'tipo_documento' => 'CC',
            'numero_documento' => $documento,
            'razon_social' => 'Cliente '.$documento,
        ]);
    }

    public function test_cada_tenant_lleva_su_propio_consecutivo_desde_uno(): void
    {
        $this->actuarComoUsuarioDe(Tenant::factory()->create());
        $primeroA = $this->crearCliente('1');
        $segundoA = $this->crearCliente('2');

        $this->actuarComoUsuarioDe(Tenant::factory()->create());
        $primeroB = $this->crearCliente('1');

        $this->assertSame(1, $primeroA->numero);
        $this->assertSame(2, $segundoA->numero);
        $this->assertSame(1, $primeroB->numero);
    }

    public function test_cada_tipo_de_documento_tiene_su_propio_contador(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertSame(1, Consecutivo::siguiente($tenant->id, 'pedidos'));
        $this->assertSame(2, Consecutivo::siguiente($tenant->id, 'pedidos'));
        $this->assertSame(1, Consecutivo::siguiente($tenant->id, 'compras'));
    }

    public function test_un_registro_eliminado_no_libera_su_numero(): void
    {
        $this->actuarComoUsuarioDe(Tenant::factory()->create());
        $this->crearCliente('1')->delete();

        $this->assertSame(2, $this->crearCliente('2')->numero);
    }

    public function test_si_la_transaccion_se_revierte_el_numero_no_se_pierde(): void
    {
        $tenant = Tenant::factory()->create();

        DB::beginTransaction();
        Consecutivo::siguiente($tenant->id, 'pedidos');
        DB::rollBack();

        $this->assertSame(1, Consecutivo::siguiente($tenant->id, 'pedidos'));
    }
}
