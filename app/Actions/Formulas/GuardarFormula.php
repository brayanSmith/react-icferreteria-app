<?php

namespace App\Actions\Formulas;

use App\Models\Formula;
use Illuminate\Support\Facades\DB;

class GuardarFormula
{
    /**
     * Crea o actualiza una fórmula junto con todo su detalle, en una sola
     * transacción: o se guarda todo o no se guarda nada.
     *
     * Al actualizar, el detalle se sincroniza por producto: los ingredientes
     * que siguen se actualizan, los nuevos se crean y los que ya no vienen se
     * eliminan.
     *
     * @param  array{nombre_formula: string, descripcion_formula?: string|null, detalles: list<array{producto_id: int|string, cantidad_producto: int|float|string}>}  $datos
     */
    public function handle(?Formula $formula, array $datos): Formula
    {
        return DB::transaction(function () use ($formula, $datos): Formula {
            $formula ??= new Formula;

            $formula->fill([
                'nombre_formula' => $datos['nombre_formula'],
                'descripcion_formula' => $datos['descripcion_formula'] ?? null,
            ])->save();

            $this->sincronizarDetalle($formula, $datos['detalles']);

            return $formula;
        });
    }

    /**
     * @param  list<array{producto_id: int|string, cantidad_producto: int|float|string}>  $detalles
     */
    private function sincronizarDetalle(Formula $formula, array $detalles): void
    {
        $productosQueSeQuedan = collect($detalles)->pluck('producto_id')->map(fn ($id): int => (int) $id);

        $formula->detalleFormulas()
            ->whereNotIn('producto_id', $productosQueSeQuedan)
            ->delete();

        foreach ($detalles as $detalle) {
            $formula->detalleFormulas()->updateOrCreate(
                ['producto_id' => (int) $detalle['producto_id']],
                ['cantidad_producto' => $detalle['cantidad_producto']],
            );
        }
    }
}
