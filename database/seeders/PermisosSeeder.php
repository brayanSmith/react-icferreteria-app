<?php

namespace Database\Seeders;

use App\Enums\Modulo;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermisosSeeder extends Seeder
{
    /**
     * Sincroniza la tabla de permisos con el catálogo definido en código.
     * Es seguro correrlo en cada despliegue: solo crea los que faltan.
     */
    public function run(): void
    {
        foreach (Modulo::permisos() as $nombre) {
            Permission::findOrCreate($nombre, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
