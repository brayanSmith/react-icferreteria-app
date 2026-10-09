<?php

namespace App\Actions\Importaciones;

use RuntimeException;

/**
 * Una fila que no se puede guardar por una razón que el usuario entiende
 * (por ejemplo, un ID que no existe). El mensaje se muestra tal cual en el
 * reporte de la importación.
 */
class FilaRechazada extends RuntimeException {}
