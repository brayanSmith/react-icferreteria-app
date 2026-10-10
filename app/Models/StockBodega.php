<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\StockBodegaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Existencias actuales de un producto en una bodega: lo que ha entrado, lo que
 * ha salido y lo que queda (stock = entradas - salidas).
 *
 * Hay un único registro por producto y bodega dentro de cada tenant. Se
 * elimina de forma lógica y se puede restaurar.
 */
class StockBodega extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<StockBodegaFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'bodega_id',
        'producto_id',
        'entradas',
        'salidas',
        'stock',
    ];

    /**
     * El stock nunca se escribe a mano: siempre se recalcula al guardar para
     * que coincida con las entradas y salidas.
     */
    protected static function booted(): void
    {
        static::saving(function (StockBodega $stock): void {
            $stock->stock = round((float) $stock->entradas - (float) $stock->salidas, 2);
        });
    }

    protected function casts(): array
    {
        return [
            'entradas' => 'decimal:2',
            'salidas' => 'decimal:2',
            'stock' => 'decimal:2',
        ];
    }

    /**
     * Bodega donde están las existencias.
     *
     * @return BelongsTo<Bodega, $this>
     */
    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    /**
     * Producto al que pertenecen las existencias.
     *
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * Movimientos (ingresos y salidas) que explican estas existencias.
     *
     * @return HasMany<DetalleStock, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleStock::class, 'stock_bodega_id');
    }
}
