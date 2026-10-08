<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleFormula extends Model
{
    //
    protected $fillable = ['formula_id', 'producto_id', 'cantidad_producto'];

    public function formula()
    {
        return $this->belongsTo(Formula::class, 'formula_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

        
}
