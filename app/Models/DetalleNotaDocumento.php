<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleNotaDocumento extends Model
{
    use SoftDeletes;
    //
    protected $fillable = [
        'nota_documento_id',
        'tipo_nota_documento',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    public function notaDocumento()
    {
        return $this->belongsTo(NotaDocumento::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
