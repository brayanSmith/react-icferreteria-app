<?php

namespace App\Models\Concerns;

use App\Models\Consecutivo;
use Illuminate\Support\Facades\Auth;

/**
 * Asigna a cada registro un número consecutivo propio de su tenant (columna
 * numero), pensado para que los usuarios identifiquen los documentos sin
 * depender del id global de la tabla.
 */
trait TieneNumeroConsecutivo
{
    public static function bootTieneNumeroConsecutivo(): void
    {
        static::creating(function ($model): void {
            $tenantId = $model->tenant_id ?? Auth::user()?->tenant_id;

            if ($model->numero === null && $tenantId !== null) {
                $model->numero = Consecutivo::siguiente($tenantId, $model->getTable());
            }
        });
    }
}
