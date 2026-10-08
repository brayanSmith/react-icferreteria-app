<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleTraslado extends Model
{
    //
    use SoftDeletes;
    protected $fillable = [
        'traslado_id',
        'producto_id',
        'cantidad',
    ];

    public function traslado()
    {
        return $this->belongsTo(Traslado::class, 'traslado_id')->withTrashed();
    }
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
