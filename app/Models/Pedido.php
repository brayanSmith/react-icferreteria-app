<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\TieneNumeroConsecutivo;
use App\Services\Pedido\PedidoCalculoService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use BelongsToTenant;

    //
    use HasFactory;
    use SoftDeletes;
    use TieneNumeroConsecutivo;

    protected $fillable = [
        'codigo',
        'fe',
        'cliente_id',
        'fecha',
        'dias_plazo_vencimiento',
        'fecha_vencimiento',
        'ciudad',
        'estado',
        'metodo_pago',
        'tipo_precio',
        'tipo_venta',
        'estado_venta',
        'canal_origen',
        'primer_comentario',
        'segundo_comentario',
        'subtotal',
        'en_cartera',
        'abono',
        'descuento',
        'retefuente',
        'reteica',
        'flete',
        'total_nota_credito',
        'total_nota_debito',
        'total_a_pagar',
        'saldo_pendiente',
        'contador_impresiones',
        'estado_vencimiento',
        'impresa',
        'estado_pago',
        'stock_retirado',
        'offline_uuid',
        'referencia',
        'bodega_id',
        'user_id',
        'alistador_id',
        'empacador_id',
        'imagen_recibido',
        'comentario_entrega',
        'motivo_devolucion',
        'fecha_ultimo_abono',
        'estado_cartera',
        'iva',
        'dias_plazo_cartera',
        'motivo_eliminacion',
        'detalle_motivo_eliminacion',
        'eliminado_por',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'fecha_vencimiento' => 'datetime',
        'fecha_ultimo_abono' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // Un pedido de CONTADO no tiene plazo: dias_plazo_vencimiento = 0 y vence el
    // mismo dia de la venta (fecha_vencimiento = fecha). Se fuerza aqui, al
    // guardar, para cubrir todos los origenes (POS, POS supermercado, tienda,
    // formulario de pedidos) sin importar lo que mande el payload. Usa el valor
    // crudo de `fecha` (no el accessor, que lo convierte a America/Bogota).
    protected static function booted(): void
    {
        static::saving(function (Pedido $pedido) {
            if ($pedido->metodo_pago === 'CONTADO' && ! empty($pedido->attributes['fecha'] ?? null)) {
                $pedido->dias_plazo_vencimiento = 0;
                $pedido->fecha_vencimiento = $pedido->attributes['fecha'];
            }
        });
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function abonoPedido()
    {
        return $this->hasMany(Abono::class);
    }

    // Alias para compatibilidad - obtener todos los abonos
    public function abonos()
    {
        return $this->hasMany(Abono::class);
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function alistador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function empacador()
    {
        return $this->belongsTo(User::class, 'empacador_id');
    }

    public function eliminadoPor()
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }

    public function detallesComision()
    {
        return $this->hasMany(DetalleComisionPedido::class, 'pedido_id');
    }

    public function notasDocumento()
    {
        return $this->hasMany(NotaDocumento::class, 'pedido_id');
    }

    public function detallePedidoGuias()
    {
        return $this->hasMany(DetallePedidoGuia::class, 'pedido_id');
    }

    public function setCodigoPedido()
    {
        $nuevoCodigo = PedidoCalculoService::generarCodigoPedido($this->id);
        $this->updateQuietly(['codigo' => $nuevoCodigo]);
    }

    // Atributo: devolver fecha en America/Bogota
    public function getFechaAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        return Carbon::parse($value)->setTimezone('America/Bogota');
    }
}
