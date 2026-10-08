<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaDocumento extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'pedido_id',
        'tipo_nota',
        'motivo',
        'observaciones',
        'subtotal',
        'descuento_total',
        'monto_total',
        'imagen',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function detallesNota()
    {
        return $this->hasMany(DetalleNotaDocumento::class);
    }
}
