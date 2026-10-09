<?php

namespace Database\Factories;

use App\Models\Importacion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Importacion>
 */
class ImportacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'modulo' => 'categorias',
            'nombre_original' => 'categorias.csv',
            'estado' => Importacion::PENDIENTE,
        ];
    }
}
