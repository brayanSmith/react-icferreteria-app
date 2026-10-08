<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleComisionPedido extends Model
{
    //
    protected $fillable = [
        'comision_id',
        'pedido_id',
        'monto_venta',
        'tipo_venta',
        'fecha_venta',
        'fecha_actualizacion_venta',
    ];

    public function comision()
    {
        return $this->belongsTo(Comision::class);
    }
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
}
