<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

    public function logoUrl(): ?string
    {
        return $this->urlDeImagen($this->logo_empresa);
    }

    public function iconoUrl(): ?string
    {
        return $this->urlDeImagen($this->icono_empresa);
    }

    private function urlDeImagen(?string $ruta): ?string
    {
        return $ruta ? Storage::disk('public')->url($ruta) : null;
    }
}
