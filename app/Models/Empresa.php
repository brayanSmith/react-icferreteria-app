<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Perfil de la empresa de un tenant (nombre, NIT, logo, etc.). Cada tenant
 * tiene una sola y puede editarla sin intervención del super administrador.
 */
class Empresa extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'nombre_empresa',
        'direccion_empresa',
        'telefono_empresa',
        'email_empresa',
        'nit_empresa',
        'logo_empresa',
        'icono_empresa',
        'cuentas_bancarias',
        'mostrar_productos_sin_inventario',
    ];

    protected $casts = [
        'cuentas_bancarias' => 'array',
        'mostrar_productos_sin_inventario' => 'boolean',
    ];
}
