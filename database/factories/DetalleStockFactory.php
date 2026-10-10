<?php

namespace Database\Factories;

use App\Models\DetalleStock;
use App\Models\Producto;
use App\Models\StockBodega;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetalleStock>
 */
class DetalleStockFactory extends Factory
{
    /**
     * Define the model's default state. Producto y StockBodega aún no tienen
     * factory: quien use esta debe pasar `stock_bodega_id` y `producto_id`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'tipo' => DetalleStock::INGRESO,
            'cantidad' => fake()->randomFloat(2, 1, 100),
            'descripcion' => fake()->sentence(),
        ];
    }

    /**
     * Movimiento de salida de inventario.
     */
    public function salida(): static
    {
        return $this->state(fn (): array => ['tipo' => DetalleStock::SALIDA]);
    }
}
