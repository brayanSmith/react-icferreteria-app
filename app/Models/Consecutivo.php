<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Contador de números consecutivos por tenant y tipo de documento.
 */
class Consecutivo extends Model
{
    protected $fillable = ['tenant_id', 'tipo', 'ultimo_numero'];

    /**
     * Reserva el siguiente número del tenant para el tipo dado.
     *
     * Bloquea la fila del contador hasta que termine la transacción, de modo que
     * dos guardados simultáneos nunca obtienen el mismo número. Si la transacción
     * externa se revierte, el número se devuelve y no quedan huecos.
     */
    public static function siguiente(int $tenantId, string $tipo): int
    {
        return DB::transaction(function () use ($tenantId, $tipo): int {
            DB::table('consecutivos')->insertOrIgnore([
                'tenant_id' => $tenantId,
                'tipo' => $tipo,
                'ultimo_numero' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $contador = DB::table('consecutivos')
                ->where('tenant_id', $tenantId)
                ->where('tipo', $tipo)
                ->lockForUpdate()
                ->first();

            $numero = $contador->ultimo_numero + 1;

            DB::table('consecutivos')
                ->where('id', $contador->id)
                ->update(['ultimo_numero' => $numero, 'updated_at' => now()]);

            return $numero;
        });
    }
}
