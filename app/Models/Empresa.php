<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Empresa extends Model
{
    //
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
        'bodega_id',
    ];

    protected $casts = [
        'cuentas_bancarias' => 'array',
        'mostrar_productos_sin_inventario' => 'boolean',
    ];

    public function bodega()
    {
        return $this->belongsTo(Bodega::class);
    }


}
