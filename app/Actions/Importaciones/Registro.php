<?php

namespace App\Actions\Importaciones;

/**
 * Módulos que se pueden importar. Para habilitar otro, basta con crear su
 * Importador y registrarlo aquí.
 */
class Registro
{
    /**
     * @return array<string, class-string<Importador>>
     */
    public static function importadores(): array
    {
        return [
            'categorias' => ImportadorCategorias::class,
            'subcategorias' => ImportadorSubCategorias::class,
        ];
    }

    public static function importador(string $modulo): ?Importador
    {
        $clase = self::importadores()[$modulo] ?? null;

        return $clase === null ? null : new $clase;
    }
}
