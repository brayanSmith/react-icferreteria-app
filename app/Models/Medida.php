<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medida extends Model
{
    //
    Use HasFactory;

    protected $fillable = ['nombre_medida', 'descripcion_medida', 'tipo_medida'];

    public function detalleProducciones()
    {
        return $this->hasMany(DetalleProduccion::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
    public function detallePedidoGuias()
    {
        return $this->hasMany(DetallePedidoGuia::class, 'medida_id');
    }

}
