<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\TieneNumeroConsecutivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use BelongsToTenant;

    //
    use HasFactory;
    use SoftDeletes;
    use TieneNumeroConsecutivo;

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'razon_social',
        'direccion',
        'telefono',
        'ciudad',
        'email',
        'representante_legal',
        'activo',
        'novedad',
        'ruta_id',
        'comercial_id',
        'tipo_cliente',
        'rut_imagen',
        'retenedor_fuente',
        'cuenta_total_pedidos_en_cartera',
        'saldo_total_pedidos_en_cartera',
        'saldo_total_pedidos_vencidos',
        'limite_credito',
        'categoria',
        'sector',
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }

    public function comercial()
    {
        return $this->belongsTo(User::class, 'comercial_id');
    }

    public function estadoPagoEnCartera()
    {
        return $this->hasMany(Pedido::class)
            ->where('estado_pago', 'EN_CARTERA');
    }

    public function estadoPagoSaldados()
    {
        return $this->hasMany(Pedido::class)
            ->where('estado_pago', 'SALDADO');
    }

    /**
     * Accessor para obtener la ruta concatenada con descripción
     */
    public function getRutaCompletaAttribute(): string
    {
        if (! $this->ruta) {
            return 'Sin ruta asignada';
        }

        $rutaNombre = $this->ruta->ruta;
        $rutaDescripcion = $this->ruta->descripcion;

        return $rutaDescripcion
            ? "Ruta: {$rutaNombre} - {$rutaDescripcion}"
            : "Ruta: {$rutaNombre}";
    }

    // Calcular la suma del saldo_pendiente de los pedidos que están en estado VENCIDO
    public function getTotalVencidoAttribute(): float
    {
        $pedidos = $this->pedidos()->get();

        return $pedidos->filter(function ($pedido) {
            return $pedido->estado_pago === 'VENCIDO';
        })->sum('saldo_pendiente');
    }

    // Calcular la suma del saldo_pendiente de los pedidos que están en estado CARTERA
    public function getTotalCarteraAttribute(): float
    {
        $pedidos = $this->pedidos()->get();

        return $pedidos->filter(function ($pedido) {
            return $pedido->estado_pago === 'EN_CARTERA' &&
                   ($pedido->estado === 'FACTURADO' || $pedido->estado === 'EN_RUTA' || $pedido->estado === 'ENTREGADO');
        })->sum('saldo_pendiente');
    }
}
