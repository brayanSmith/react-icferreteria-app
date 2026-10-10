<?php

namespace App\Actions\Exportaciones;

use App\Enums\Modulo;
use App\Models\Puc;

/**
 * Exporta el plan de cuentas con las mismas columnas que acepta la
 * importación, así que el archivo se puede editar y volver a importar para
 * actualizar cuentas (la subcuenta las identifica).
 */
class ExportadorPucs extends Exportador
{
    public function modulo(): Modulo
    {
        return Modulo::Pucs;
    }

    public function titulo(): string
    {
        return 'Plan de cuentas (PUC)';
    }

    public function encabezados(): array
    {
        return ['Clase', 'Cuenta', 'Subcuenta', 'Concepto', 'Descripción'];
    }

    public function filas(): iterable
    {
        foreach (Puc::query()->orderBy('subcuenta')->lazy(500) as $puc) {
            yield [$puc->tipo, $puc->cuenta, $puc->subcuenta, $puc->concepto, $puc->descripcion ?? ''];
        }
    }
}
