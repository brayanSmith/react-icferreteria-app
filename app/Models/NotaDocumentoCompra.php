<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaDocumentoCompra extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'compra_id',
        'tipo_nota',
        'motivo',
        'observaciones',
        'subtotal',
        'descuento_total',
        'monto_total',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function detallesNota()
    {
        return $this->hasMany(DetalleNotaDocumentoCompra::class);
    }
}
