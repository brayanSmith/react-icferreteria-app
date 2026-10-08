<?php

namespace Tests\Feature;

use App\Models\Proveedor;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\UniqueConPapelera;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UniqueConPapeleraTest extends TestCase
{
    use RefreshDatabase;

    private function actuarComoUsuarioDe(Tenant $tenant): void
    {
        $this->actingAs(User::factory()->create(['tenant_id' => $tenant->id]));
    }

    private function crearProveedor(string $nit): Proveedor
    {
        return Proveedor::create([
            'nit_proveedor' => $nit,
            'nombre_proveedor' => 'Proveedor '.$nit,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function errores(string $codigo, int|string|null $ignorarId = null): array
    {
        $validator = Validator::make(
            ['nit_proveedor' => $codigo],
            ['nit_proveedor' => [new UniqueConPapelera(Proveedor::class, 'nit_proveedor', $ignorarId)]],
        );

        return $validator->errors()->get('nit_proveedor');
    }

    public function test_acepta_un_valor_que_no_existe(): void
    {
        $this->actuarComoUsuarioDe(Tenant::factory()->create());

        $this->assertSame([], $this->errores('NUEVO'));
    }

    public function test_rechaza_un_valor_usado_por_un_registro_activo(): void
    {
        $this->actuarComoUsuarioDe(Tenant::factory()->create());
        $this->crearProveedor('A1');

        $this->assertStringContainsString('ya está en uso', $this->errores('A1')[0]);
    }

    public function test_avisa_que_se_puede_restaurar_cuando_el_registro_esta_eliminado(): void
    {
        $this->actuarComoUsuarioDe(Tenant::factory()->create());
        $this->crearProveedor('A1')->delete();

        $this->assertStringContainsString('restáuralo', $this->errores('A1')[0]);
    }

    public function test_ignora_el_registro_que_se_esta_editando(): void
    {
        $this->actuarComoUsuarioDe(Tenant::factory()->create());
        $proveedor = $this->crearProveedor('A1');

        $this->assertSame([], $this->errores('A1', $proveedor->id));
    }

    public function test_no_considera_los_registros_de_otro_tenant(): void
    {
        $this->actuarComoUsuarioDe(Tenant::factory()->create());
        $this->crearProveedor('A1');

        $this->actuarComoUsuarioDe(Tenant::factory()->create());

        $this->assertSame([], $this->errores('A1'));
    }
}
