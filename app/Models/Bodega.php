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

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    /**
     * Traslados en los que esta bodega entrega mercancía.
     */
    public function trasladosComoDonante()
    {
        return $this->hasMany(Traslado::class, 'bodega_donante_id');
    }

    /**
     * Traslados en los que esta bodega recibe mercancía.
     */
    public function trasladosComoDestino()
    {
        return $this->hasMany(Traslado::class, 'bodega_destino_id');
    }

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    public function stockIniciales()
    {
        return $this->hasMany(StockInicial::class);
    }

    public function stockBodegas()
    {
        return $this->hasMany(StockBodega::class);
    }
}
