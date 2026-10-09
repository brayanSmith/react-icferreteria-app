<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cantidad con la que arranca el inventario de un producto en una bodega.
 *
 * Hay un único registro por producto y bodega dentro de cada tenant. Se
 * elimina de forma lógica y se puede restaurar.
 */
class StockInicial extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'bodega_id',
        'producto_id',
        'cantidad',
    ];

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:2'];
    }

    /**
     * Producto al que pertenece el stock.
     *
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Bodega donde se guarda el stock.
     *
     * @return BelongsTo<Bodega, $this>
     */
    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class);
    }
}
