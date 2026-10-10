<?php

namespace App\Actions\Importaciones;

/**
 * Conversores para leer los valores tal como los escribe la gente en Excel:
 * números con coma decimal, nombres legibles de una lista y sí/no escritos de
 * varias formas. Los usan los importadores en su método `normalizar`.
 *
 * Si un valor no se puede interpretar, se devuelve como vino para que la
 * validación de la fila lo rechace con su mensaje.
 */
trait InterpretaValores
{
    /**
     * Convierte "1.234,50", "1,5" o "$ 2000" en un número con punto decimal.
     */
    protected static function numero(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $limpio = str_replace([' ', '$'], '', $valor);
        $coma = strrpos($limpio, ',');
        $punto = strrpos($limpio, '.');

        if ($coma !== false && $punto !== false) {
            // El separador que aparece al final es el decimal; el otro agrupa miles.
            $limpio = $coma > $punto
                ? str_replace(',', '.', str_replace('.', '', $limpio))
                : str_replace(',', '', $limpio);
        } elseif ($coma !== false) {
            $limpio = str_replace(',', '.', $limpio);
        }

        return is_numeric($limpio) ? $limpio : $valor;
    }

    /**
     * Acepta el valor interno (MATERIA_PRIMA) o su nombre legible (Materia prima).
     *
     * @param  array<string, string>  $opciones  valor interno => etiqueta
     * @param  array<string, string>  $alias  otro nombre normalizado => valor interno
     */
    protected static function deLista(?string $valor, array $opciones, array $alias = []): ?string
    {
        if ($valor === null) {
            return null;
        }

        $buscado = LectorCsv::normalizar($valor);

        foreach ($opciones as $interno => $etiqueta) {
            if ($buscado === LectorCsv::normalizar($interno) || $buscado === LectorCsv::normalizar($etiqueta)) {
                return $interno;
            }
        }

        return $alias[$buscado] ?? $valor;
    }

    /**
     * Sí/no escrito de varias formas: "Sí", "si", "1", "activo"... => "1"; "No", "0", "inactivo"... => "0".
     */
    protected static function booleano(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        return match (LectorCsv::normalizar($valor)) {
            '1', 'si', 'true', 'verdadero', 'activo', 'x' => '1',
            '0', 'no', 'false', 'falso', 'inactivo' => '0',
            default => $valor,
        };
    }

    /**
     * Compara un valor guardado con uno nuevo: los números por su valor (10 = 10.00)
     * y los sí/no como booleanos.
     */
    protected static function sonIguales(mixed $actual, mixed $nuevo): bool
    {
        if (is_bool($nuevo)) {
            return (bool) $actual === $nuevo;
        }

        if (is_numeric($actual) && is_numeric($nuevo)) {
            return abs((float) $actual - (float) $nuevo) < 0.00001;
        }

        return (string) $actual === (string) $nuevo;
    }
}
