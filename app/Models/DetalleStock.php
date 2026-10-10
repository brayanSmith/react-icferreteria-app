<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\DetalleStockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Movimiento de inventario: cada ingreso o salida de un producto en una
 * bodega, con quién lo hizo y el documento que lo originó. Es el historial
 * detrás de las existencias que resume StockBodega.
 *
 * `origen` es la línea o el documento que lo generó (un stock inicial, la línea
 * de un pedido...); en los movimientos hechos a mano es nulo. `descripcion` es el
 * detalle legible. Se elimina de forma lógica.
 */
class DetalleStock extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<DetalleStockFactory> */
    use HasFactory;

    use SoftDeletes;

    public const INGRESO = 'INGRESO';

    public const SALIDA = 'SALIDA';

    /**
     * Tipos de movimiento válidos.
     *
     * @var list<string>
     */
    public const TIPOS = [self::INGRESO, self::SALIDA];

    protected $fillable = [
        'stock_bodega_id',
        'producto_id',
        'user_id',
        'tipo',
        'cantidad',
        'descripcion',
    ];

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:2'];
    }

    /**
     * Existencias (producto en una bodega) a las que afecta el movimiento.
     *
     * @return BelongsTo<StockBodega, $this>
     */
    public function stockBodega(): BelongsTo
    {
        return $this->belongsTo(StockBodega::class, 'stock_bodega_id');
    }

    /**
     * Producto que entró o salió.
     *
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * Usuario que registró el movimiento.
     *
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Línea o documento que generó el movimiento; nulo si se registró a mano.
     *
     * @return MorphTo<Model, $this>
     */
    public function origen(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Los movimientos hechos a mano son los únicos que se corrigen desde el
     * stock por bodega; los demás se corrigen en el documento que los generó.
     */
    public function esManual(): bool
    {
        return $this->origen_type === null;
    }

    /**
     * Nombre legible del origen para mostrar en pantalla.
     */
    public function etiquetaOrigen(): string
    {
        return match ($this->origen_type) {
            null => 'Manual',
            'stock_inicial' => 'Stock inicial',
            default => class_basename($this->origen_type),
        };
    }

    public function esIngreso(): bool
    {
        return $this->tipo === self::INGRESO;
    }
}
