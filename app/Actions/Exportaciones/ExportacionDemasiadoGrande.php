<?php

namespace App\Actions\Exportaciones;

use RuntimeException;

/**
 * El PDF no admite tantas filas; hay que exportar a Excel o CSV.
 */
class ExportacionDemasiadoGrande extends RuntimeException
{
    public function __construct(public readonly int $maximo)
    {
        parent::__construct("El PDF admite hasta {$maximo} filas.");
    }
}
