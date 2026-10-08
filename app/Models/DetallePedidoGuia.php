<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetallePedidoGuia extends Model
{
    //
    use SoftDeletes;
    protected $fillable = [
        'pedido_id',
        'medida_id',
        'cantidad',
        'descripcion',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function medida()
    {
        return $this->belongsTo(Medida::class);
    }
}
