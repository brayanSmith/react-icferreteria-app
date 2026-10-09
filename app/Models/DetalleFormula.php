<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un ingrediente de una fórmula: un producto y la cantidad que lleva.
 *
 * No tiene tenant propio: pertenece al de su fórmula.
 */
class DetalleFormula extends Model
{
    protected $fillable = ['formula_id', 'producto_id', 'cantidad_producto'];

    protected function casts(): array
    {
        return ['cantidad_producto' => 'decimal:2'];
    }

    /**
     * @return BelongsTo<Formula, $this>
     */
    public function formula(): BelongsTo
    {
        return $this->belongsTo(Formula::class, 'formula_id');
    }

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
