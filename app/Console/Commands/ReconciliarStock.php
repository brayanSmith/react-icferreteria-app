<?php

namespace App\Console\Commands;

use App\Actions\Stock\ReconciliarStock as Reconciliar;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('stock:reconciliar {--tenant= : Solo revisa la empresa con este ID} {--corregir : Ajusta las existencias para que coincidan con sus movimientos}')]
#[Description('Compara las existencias de cada bodega con la suma de sus movimientos y avisa de las diferencias')]
class ReconciliarStock extends Command
{
    /**
     * Termina con error si queda alguna diferencia sin corregir, para poder
     * usarlo en una tarea programada que avise cuando algo no cuadra.
     */
    public function handle(Reconciliar $reconciliar): int
    {
        $tenantId = $this->option('tenant') === null ? null : (int) $this->option('tenant');

        $diferencias = $reconciliar->revisar($tenantId);
        $ajenos = $reconciliar->movimientosDeOtroProducto($tenantId);

        if ($diferencias === [] && $ajenos === []) {
            $this->info('Todo cuadra: las existencias coinciden con sus movimientos.');

            return self::SUCCESS;
        }

        if ($diferencias !== []) {
            $this->warn(count($diferencias).' existencia(s) no coinciden con sus movimientos:');
            $this->table(
                ['Empresa', 'Existencia', 'Producto', 'Bodega', 'Entradas', 'Deberían ser', 'Salidas', 'Deberían ser', 'Stock', 'Debería ser'],
                array_map(fn (array $d): array => [
                    $d['tenant_id'], $d['stock_bodega_id'], $d['producto_id'], $d['bodega_id'],
                    $d['entradas'], $d['entradas_reales'], $d['salidas'], $d['salidas_reales'], $d['stock'], $d['stock_real'],
                ], $diferencias),
            );
        }

        if ($ajenos !== []) {
            $this->error(count($ajenos).' movimiento(s) pertenecen a otro producto que las existencias a las que están enlazados (revisar a mano):');
            $this->table(
                ['Empresa', 'Movimiento', 'Existencia', 'Producto del movimiento', 'Producto de la existencia'],
                array_map(fn (array $a): array => [$a['tenant_id'], $a['movimiento_id'], $a['stock_bodega_id'], $a['producto_del_movimiento'], $a['producto_del_stock']], $ajenos),
            );
        }

        if ($diferencias !== [] && $this->option('corregir')) {
            $corregidas = $reconciliar->corregir($diferencias);
            $this->info("Se corrigieron {$corregidas} existencia(s).");

            return $ajenos === [] ? self::SUCCESS : self::FAILURE;
        }

        if ($diferencias !== []) {
            $this->line('Vuelve a correr el comando con --corregir para ajustar las existencias a sus movimientos.');
        }

        return self::FAILURE;
    }
}
