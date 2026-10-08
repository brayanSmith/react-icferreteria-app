<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleComisionAbono extends Model
{
    //
    protected $fillable = [
        'comision_id',
        'abono_id',
        'monto_abono',
        'fecha_abono',
    ];

    public function comision()
    {
        return $this->belongsTo(Comision::class, 'comision_id');
    }
    public function abonoPedido()
    {
        return $this->belongsTo(Abono::class, 'abono_id');
    }
}
