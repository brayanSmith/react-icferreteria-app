<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleCompra extends Model
{
    /** @use HasFactory<\Database\Factories\DetalleCompraFactory> */
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'compra_id',
        //'producto_id',
        'item_id',
        'descripcion_item',
        'cantidad',
        'precio_unitario',
        'descuento',
        'precio_con_descuento',
        'precio_ferretero',
        'precio_mayorista',
        'precio_detal',
        'precio_especial',
        'iva',
        'precio_con_iva',
        'subtotal',
        'tipo_item',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'item_id');
    }

    public function puc()
    {
        return $this->belongsTo(Puc::class, 'item_id');
    }
}
