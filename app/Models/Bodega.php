<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bodega extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use SoftDeletes;

    //
    protected $fillable = ['nombre_bodega', 'ubicacion_bodega'];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    public function traslados()
    {
        return $this->hasMany(Traslado::class);
    }

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    public function stockBodegas()
    {
        return $this->hasMany(StockBodega::class);
    }
}
