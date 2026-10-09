<?php

namespace App\Actions\Exportaciones;

use App\Enums\Modulo;

/**
 * Contrato de un módulo exportable. Cada módulo dice cómo se llama el
 * archivo, qué columnas lleva y de dónde salen las filas; el formato
 * (CSV, TXT, XLSX o PDF) lo resuelve EscritorDeArchivo.
 */
abstract class Exportador
{
    /**
     * Módulo de permisos al que pertenece; exportar exige poder verlo.
     */
    abstract public function modulo(): Modulo;

    /**
     * Título del documento (se usa en el PDF).
     */
    abstract public function titulo(): string;

    /**
     * @return list<string>
     */
    abstract public function encabezados(): array;

    /**
     * Filas del archivo, una a una (sin cargar todo en memoria).
     *
     * @return iterable<int, list<string|int|float|null>>
     */
    abstract public function filas(): iterable;
}
