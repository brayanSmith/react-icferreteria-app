<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\TieneNumeroConsecutivo;
use Database\Factories\CompraFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compra extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<CompraFactory> */
    use HasFactory;

    use SoftDeletes;
    use TieneNumeroConsecutivo;

    protected $fillable = [
        'offline_uuid',
        'factura',
        'proveedor_id',
        'fecha',
        'dias_plazo_vencimiento',
        'fecha_vencimiento',
        'metodo_pago',
        'estado_pago',
        'tipo_compra',
        'estado',
        'observaciones',
        'subtotal',
        'retefuente',
        'reteica',
        'flete',
        'abono',
        'descuento',
        'total_a_pagar',
        'categoria_compra',
        'item_compra',
        'bodega_id',
        'saldo_pendiente',
        'solicitado',
        'total_nota_credito',
        'total_nota_debito',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function notasDocumentoCompra()
    {
        return $this->hasMany(NotaDocumentoCompra::class, 'compra_id');
    }

    protected function titulo(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->id} - {$this->factura}",
        );
    }

    public function detallesCompra()
    {
        return $this->hasMany(DetalleCompra::class);
    }

    public function abonoCompra()
    {
        return $this->hasMany(AbonoCompra::class);
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }
}
