<?php

namespace App\Actions\Archivos;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class GuardarArchivoPublico
{
    /**
     * Disco donde se guardan los archivos (servidos desde /storage).
     */
    private const DISCO = 'public';

    /**
     * Decide qué ruta debe quedar guardada para un archivo opcional.
     *
     * - Con un archivo nuevo: lo guarda en `$directorio`, borra el anterior y
     *   devuelve la ruta nueva.
     * - Con `$quitar`: borra el anterior y devuelve null.
     * - Sin cambios: devuelve la ruta actual intacta.
     */
    public function reemplazar(?string $actual, ?UploadedFile $nuevo, bool $quitar, string $directorio): ?string
    {
        if ($nuevo === null && ! $quitar) {
            return $actual;
        }

        if ($actual !== null) {
            Storage::disk(self::DISCO)->delete($actual);
        }

        return $nuevo?->store($directorio, self::DISCO);
    }

    /**
     * URL pública del archivo, o null si no hay.
     */
    public function url(?string $ruta): ?string
    {
        return $ruta ? Storage::disk(self::DISCO)->url($ruta) : null;
    }
}
