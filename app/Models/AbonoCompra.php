<?php

namespace App\Models;

use Database\Factories\AbonoCompraFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AbonoCompra extends Model
{
    /** @use HasFactory<AbonoCompraFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'compra_id',
        'fecha_abono_compra',
        'monto_abono_compra',
        'forma_pago_abono_compra',
        'descripcion_abono_compra',
        'imagen_abono_compra',
        'user_id',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function formaPagoAbonoCompra()
    {
        return $this->belongsTo(Puc::class, 'forma_pago_abono_compra');
    }
}
