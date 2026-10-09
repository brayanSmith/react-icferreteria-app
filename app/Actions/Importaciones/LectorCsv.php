<?php

namespace App\Actions\Importaciones;

use Generator;
use Illuminate\Support\Str;
use League\Csv\CharsetConverter;
use League\Csv\Info;
use League\Csv\Reader;

/**
 * Lee un CSV sin que el usuario tenga que decir cómo está hecho: detecta la
 * codificación (UTF-8 o la de Excel en Windows) y el delimitador (coma,
 * punto y coma, tabulador o barra vertical), y entrega las filas de una en
 * una para no cargar el archivo completo en memoria.
 */
class LectorCsv
{
    private const DELIMITADORES = [',', ';', "\t", '|'];

    private Reader $lector;

    private string $delimitador;

    public function __construct(string $ruta)
    {
        $this->lector = Reader::createFromPath($ruta, 'r');
        $this->lector->skipEmptyRecords();

        $codificacion = $this->detectarCodificacion($ruta);

        if ($codificacion !== 'UTF-8') {
            CharsetConverter::addTo($this->lector, $codificacion, 'UTF-8');
        }

        $this->delimitador = $this->detectarDelimitador();
        $this->lector->setDelimiter($this->delimitador);
    }

    public function delimitador(): string
    {
        return $this->delimitador;
    }

    /**
     * Encabezados tal como vienen en el archivo (sin BOM ni espacios sobrantes).
     *
     * @return list<string>
     */
    public function encabezados(): array
    {
        foreach ($this->lector->getRecords() as $fila) {
            return array_map(
                fn (?string $celda): string => trim(str_replace("\xEF\xBB\xBF", '', $celda ?? '')),
                array_values($fila),
            );
        }

        return [];
    }

    /**
     * Las primeras filas de datos, para que el usuario vea qué trae cada columna.
     *
     * @return list<list<string|null>>
     */
    public function muestra(int $cantidad = 3): array
    {
        $muestra = [];

        foreach ($this->filas() as $fila) {
            $muestra[] = $fila;

            if (count($muestra) >= $cantidad) {
                break;
            }
        }

        return $muestra;
    }

    /**
     * Cantidad de filas de datos (sin contar el encabezado).
     */
    public function totalFilas(): int
    {
        return max(0, iterator_count($this->lector->getRecords()) - 1);
    }

    /**
     * Filas de datos, cada una como lista de celdas por posición (vacías = null).
     * La clave es el número de registro dentro del archivo (el encabezado es el 1).
     *
     * @return Generator<int, list<string|null>>
     */
    public function filas(): Generator
    {
        $numero = 0;

        foreach ($this->lector->getRecords() as $fila) {
            $numero++;

            if ($numero === 1) {
                continue;
            }

            yield $numero => array_map(function (?string $celda): ?string {
                $valor = trim($celda ?? '');

                return $valor === '' ? null : $valor;
            }, array_values($fila));
        }
    }

    /**
     * Deja un encabezado en forma comparable: "Subcategoría " => "subcategoria".
     */
    public static function normalizar(string $encabezado): string
    {
        $limpio = str_replace("\xEF\xBB\xBF", '', $encabezado);

        return trim((string) Str::of($limpio)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_'), '_');
    }

    /**
     * UTF-8 si la muestra inicial lo es; si no, se asume Windows-1252 (Excel en español).
     */
    private function detectarCodificacion(string $ruta): string
    {
        $muestra = (string) file_get_contents($ruta, false, null, 0, 65536);

        // La muestra puede cortar un carácter por la mitad: se descartan los últimos bytes.
        $muestra = substr($muestra, 0, max(0, strlen($muestra) - 4));

        return mb_check_encoding($muestra, 'UTF-8') ? 'UTF-8' : 'Windows-1252';
    }

    /**
     * El delimitador que más campos produce en las primeras filas.
     */
    private function detectarDelimitador(): string
    {
        $conteo = Info::getDelimiterStats($this->lector, self::DELIMITADORES, 10);
        arsort($conteo);

        $mejor = array_key_first($conteo);

        return $mejor !== null && $conteo[$mejor] > 0 ? (string) $mejor : ',';
    }
}
