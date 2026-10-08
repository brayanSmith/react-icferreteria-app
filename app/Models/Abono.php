<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Abono extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'fecha',
        'monto',
        'descripcion',
        'imagen',
        'pedido_id',
        'forma_pago',
        'user_id',
        'vendedor_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public function calcularFechaVencimiento($dias = 30)
    {
        if ($this->fecha) {
            return $this->fecha->addDays($dias);
        }
        return null;
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
    public function formaPago()
    {
        return $this->belongsTo(Puc::class, 'forma_pago');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function detallesComisionAbonos()
    {
        return $this->hasMany(DetalleComisionAbono::class, 'abono_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }
}
