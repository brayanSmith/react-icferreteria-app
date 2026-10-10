<?php

namespace App\Actions\Stock;

use App\Models\DetalleStock;
use App\Models\StockBodega;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Único punto por el que se mueve el inventario de una bodega.
 *
 * Cada ingreso o salida queda como un DetalleStock y, en la misma transacción,
 * actualiza las entradas y salidas de su StockBodega (el stock se recalcula
 * solo). Así el resumen y el historial no se pueden desincronizar. Un
 * movimiento nunca puede dejar el stock en negativo.
 *
 * Hay dos formas de usarlo:
 * - `registrar`, `corregir` y `anular`, para los movimientos hechos a mano.
 * - `sincronizar`, para los documentos (un stock inicial, la línea de un
 *   pedido...): el documento dice cómo quedó y aquí se ajusta el inventario.
 */
class MoverStock
{
    /**
     * Hace que el inventario refleje el estado actual de una línea o documento.
     *
     * Cada origen tiene a lo sumo un movimiento. Según lo que ya exista:
     * - sin línea (o con cantidad cero): se anula su movimiento, si lo tiene;
     * - sin movimiento previo: se registra uno nuevo;
     * - mismo producto y bodega: se corrige el movimiento (tipo y cantidad), y la
     *   descripción deja constancia de la corrección;
     * - otro producto u otra bodega: se anula el anterior y se registra uno nuevo.
     *
     * Es seguro llamarlo las veces que haga falta: si nada cambió, no hace nada.
     * Debe llamarse dentro de la misma transacción que guarda el documento, para
     * que un rechazo por falta de stock deshaga también el cambio del documento.
     *
     * @param  array{producto_id: int, bodega_id: int, tipo: string, cantidad: float|int|string}|null  $linea
     *
     * @throws ValidationException Si el ajuste dejaría el stock en negativo.
     */
    public function sincronizar(Model $origen, ?array $linea, int $usuarioId, string $descripcion): ?DetalleStock
    {
        return DB::transaction(function () use ($origen, $linea, $usuarioId, $descripcion): ?DetalleStock {
            $actual = DetalleStock::query()
                ->where('origen_type', $origen->getMorphClass())
                ->where('origen_id', $origen->getKey())
                ->with('stockBodega')
                ->first();

            $cantidad = $linea === null ? 0.0 : round((float) $linea['cantidad'], 2);

            if ($linea === null || $cantidad <= 0) {
                if ($actual !== null) {
                    $this->anular($actual);
                }

                return null;
            }

            if ($actual === null) {
                return $this->registrar($linea['producto_id'], $linea['bodega_id'], $usuarioId, $linea['tipo'], $cantidad, $descripcion, $origen);
            }

            $mismoLugar = $actual->producto_id === (int) $linea['producto_id']
                && $actual->stockBodega->bodega_id === (int) $linea['bodega_id'];

            if (! $mismoLugar) {
                $this->anular($actual);

                return $this->registrar($linea['producto_id'], $linea['bodega_id'], $usuarioId, $linea['tipo'], $cantidad, $descripcion, $origen);
            }

            if ($actual->tipo === $linea['tipo'] && round((float) $actual->cantidad, 2) === $cantidad) {
                return $actual;
            }

            return $this->corregir(
                $actual,
                $linea['tipo'],
                $cantidad,
                $this->descripcionCorregida($descripcion, (float) $actual->cantidad, $cantidad, $usuarioId),
            );
        });
    }

    /**
     * Registra un movimiento nuevo, creando las existencias del producto en la
     * bodega si todavía no existían. Sin `$origen`, es un movimiento hecho a mano.
     *
     * @throws ValidationException Si una salida supera lo que hay en la bodega.
     */
    public function registrar(
        int $productoId,
        int $bodegaId,
        int $usuarioId,
        string $tipo,
        float $cantidad,
        string $descripcion,
        ?Model $origen = null,
    ): DetalleStock {
        return DB::transaction(function () use ($productoId, $bodegaId, $usuarioId, $tipo, $cantidad, $descripcion, $origen): DetalleStock {
            $stock = $this->existenciasBloqueadas($productoId, $bodegaId);

            $this->sumar($stock, $tipo, $cantidad);
            $this->guardar($stock);

            $movimiento = $stock->detalles()->make([
                'producto_id' => $productoId,
                'user_id' => $usuarioId,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'descripcion' => $descripcion,
            ]);

            if ($origen !== null) {
                $movimiento->origen()->associate($origen);
            }

            $movimiento->forceFill(['tenant_id' => $stock->tenant_id])->save();

            return $movimiento;
        });
    }

    /**
     * Corrige un movimiento: deshace su efecto y aplica el nuevo.
     *
     * @throws ValidationException Si la corrección dejaría el stock en negativo.
     */
    public function corregir(DetalleStock $movimiento, string $tipo, float $cantidad, string $descripcion): DetalleStock
    {
        return DB::transaction(function () use ($movimiento, $tipo, $cantidad, $descripcion): DetalleStock {
            $stock = StockBodega::query()->lockForUpdate()->findOrFail($movimiento->stock_bodega_id);

            // Se deshace y se rehace en memoria y se valida una sola vez al final: el estado
            // intermedio puede ser negativo aunque el resultado final no lo sea.
            $this->sumar($stock, $movimiento->tipo, -(float) $movimiento->cantidad);
            $this->sumar($stock, $tipo, $cantidad);
            $this->guardar($stock);

            $movimiento->update([
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'descripcion' => $descripcion,
            ]);

            return $movimiento;
        });
    }

    /**
     * Elimina un movimiento y deshace su efecto en las existencias.
     *
     * @throws ValidationException Si deshacerlo dejaría el stock en negativo
     *                             (por ejemplo, un ingreso cuya mercancía ya salió).
     */
    public function anular(DetalleStock $movimiento): void
    {
        DB::transaction(function () use ($movimiento): void {
            $stock = StockBodega::query()->lockForUpdate()->findOrFail($movimiento->stock_bodega_id);

            $this->sumar($stock, $movimiento->tipo, -(float) $movimiento->cantidad);
            $this->guardar($stock);

            $movimiento->delete();
        });
    }

    /**
     * Descripción de un movimiento corregido, con el valor anterior y quién lo cambió.
     */
    private function descripcionCorregida(string $descripcion, float $antes, float $despues, int $usuarioId): string
    {
        $quien = User::query()->whereKey($usuarioId)->value('name') ?? 'un usuario';

        return sprintf('%s · corregido de %s a %s por %s', $descripcion, $this->numero($antes), $this->numero($despues), $quien);
    }

    private function numero(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }

    /**
     * Existencias del producto en la bodega, bloqueadas durante la transacción.
     * Si no existen se crean en cero; si estaban eliminadas se restauran,
     * porque la base solo admite un registro por producto y bodega.
     */
    private function existenciasBloqueadas(int $productoId, int $bodegaId): StockBodega
    {
        $stock = StockBodega::withTrashed()
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->lockForUpdate()
            ->first();

        if ($stock === null) {
            return StockBodega::create([
                'producto_id' => $productoId,
                'bodega_id' => $bodegaId,
                'entradas' => 0,
                'salidas' => 0,
            ]);
        }

        if ($stock->trashed()) {
            $stock->restore();
        }

        return $stock;
    }

    /**
     * Suma (o resta, con cantidad negativa) un movimiento a las existencias, sin guardar.
     */
    private function sumar(StockBodega $stock, string $tipo, float $cantidad): void
    {
        if ($tipo === DetalleStock::INGRESO) {
            $stock->entradas = round((float) $stock->entradas + $cantidad, 2);
        } else {
            $stock->salidas = round((float) $stock->salidas + $cantidad, 2);
        }
    }

    /**
     * Guarda las existencias, siempre que no queden en negativo.
     *
     * @throws ValidationException
     */
    private function guardar(StockBodega $stock): void
    {
        if ((float) $stock->entradas - (float) $stock->salidas < 0) {
            throw ValidationException::withMessages([
                'cantidad' => 'No hay suficiente stock en la bodega: este movimiento lo dejaría en negativo.',
            ]);
        }

        $stock->save();
    }
}
