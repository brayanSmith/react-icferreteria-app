<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Traslado extends Model
{
    /** @use HasFactory<\Database\Factories\TrasladoFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'bodega_donante_id',
        'bodega_destino_id',
        //'producto_id',
        //'cantidad',
        'observaciones',
        'created_at',
        'updated_at',
        'user_id', // Agregar el campo user_id al arreglo fillable
    ];

    public function bodegaDonante()
    {
        return $this->belongsTo(Bodega::class, 'bodega_donante_id');
    }
    public function bodegaDestino()
    {
        return $this->belongsTo(Bodega::class, 'bodega_destino_id');
    }
    public function detalles()
    {
        return $this->hasMany(DetalleTraslado::class, 'traslado_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }*/
}
