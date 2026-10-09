<?php

namespace App\Actions\Importaciones;

use App\Enums\Modulo;
use App\Models\Importacion;

/**
 * Contrato de un módulo importable desde CSV.
 *
 * Cada módulo (categorías, productos...) declara sus columnas, las reglas de
 * validación de cada fila y cómo se guarda. El usuario relaciona las columnas
 * de su archivo con estas al subirlo, y elige si el archivo crea registros
 * nuevos o actualiza los existentes (que se identifican por su ID). El
 * trabajo en segundo plano (ProcesarImportacion) se ocupa del resto: leer por
 * lotes, validar, contar y reportar los errores.
 */
abstract class Importador
{
    /**
     * Módulo de permisos al que pertenece; importar exige poder crear en él.
     */
    abstract public function modulo(): Modulo;

    /**
     * Nombre que ve el usuario (por ejemplo, "Categorías").
     */
    abstract public function etiqueta(): string;

    /**
     * Columnas que entiende el sistema: clave interna => nombre en la plantilla.
     *
     * @return array<string, string>
     */
    abstract public function columnas(): array;

    /**
     * Clave de la columna con el ID del registro (la que identifica qué se actualiza).
     */
    abstract public function columnaId(): string;

    /**
     * Columnas que el usuario debe relacionar según el modo. En "crear" no hace
     * falta el ID; "actualizar" y "ambos" lo necesitan.
     *
     * @return list<string>
     */
    abstract public function columnasRequeridas(string $modo): array;

    /**
     * Qué se hace con una fila: en "ambos", la que trae ID actualiza y la que no, crea.
     *
     * @param  array<string, string|null>  $datos
     */
    public function modoDeFila(array $datos, string $modo): string
    {
        if ($modo !== Importacion::MODO_AMBOS) {
            return $modo;
        }

        return $datos[$this->columnaId()] === null ? Importacion::MODO_CREAR : Importacion::MODO_ACTUALIZAR;
    }

    /**
     * Reglas de validación de una fila, indexadas por clave de columna.
     *
     * @return array<string, array<int, mixed>|string>
     */
    abstract public function reglas(string $modo): array;

    /**
     * Guarda una fila ya validada. Para rechazarla con un motivo claro se
     * lanza FilaRechazada.
     *
     * @param  array<string, string|null>  $datos
     * @return bool true si creó o cambió algo; false si no hubo nada que hacer.
     *
     * @throws FilaRechazada
     */
    abstract public function guardar(array $datos, string $modo): bool;

    /**
     * Filas de ejemplo que lleva la plantilla descargable.
     *
     * @return list<list<string>>
     */
    abstract public function ejemplos(): array;

    /**
     * Otros nombres con los que suele venir una columna, para sugerir la relación.
     *
     * @return array<string, list<string>>
     */
    public function sinonimos(): array
    {
        return [];
    }

    /**
     * Sugiere qué columna del archivo corresponde a cada columna del sistema,
     * comparando nombres sin tildes ni mayúsculas. Si no encuentra ninguna, null.
     *
     * @param  list<string>  $encabezados  Encabezados del archivo.
     * @return array<string, int|null> clave del sistema => posición en el archivo.
     */
    public function sugerirMapeo(array $encabezados): array
    {
        $normalizados = array_map(LectorCsv::normalizar(...), $encabezados);
        $mapeo = [];

        foreach ($this->columnas() as $clave => $etiqueta) {
            $candidatos = array_map(LectorCsv::normalizar(...), [$clave, $etiqueta, ...($this->sinonimos()[$clave] ?? [])]);
            $posicion = array_search(true, array_map(fn (string $encabezado): bool => in_array($encabezado, $candidatos, true), $normalizados), true);

            $mapeo[$clave] = $posicion === false ? null : $posicion;
        }

        return $mapeo;
    }
}
