<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Crea el super administrador de la plataforma (usuario sin tenant).
     */
    public function run(): void
    {
        $superAdmin = User::query()->firstOrNew(['email' => 'admin@admin.com']);

        $superAdmin->forceFill([
            'name' => 'Super Administrador',
            'password' => 'password123',
            'n_documento' => '0000000000',
            'tenant_id' => null,
            'es_super_admin' => true,
            'activo' => true,
            'email_verified_at' => now(),
        ])->save();
    }
}
