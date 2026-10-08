<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleNotaDocumentoCompra extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'nota_documento_compra_id',
        'tipo_nota_documento',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    public function notaDocumentoCompra()
    {
        return $this->belongsTo(NotaDocumentoCompra::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
