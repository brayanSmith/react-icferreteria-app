<?php

namespace App\Actions\Exportaciones;

/**
 * Módulos que se pueden exportar. Para habilitar otro, basta con crear su
 * Exportador y registrarlo aquí.
 */
class Registro
{
    /**
     * @return array<string, class-string<Exportador>>
     */
    public static function exportadores(): array
    {
        return [
            'categorias' => ExportadorCategorias::class,
            'pucs' => ExportadorPucs::class,
            'productos' => ExportadorProductos::class,
            'clientes' => ExportadorClientes::class,
            'usuarios' => ExportadorUsuarios::class,
            'stock_inicial' => ExportadorStockInicial::class,
            'stock_bodegas' => ExportadorStockBodegas::class,
        ];
    }

    public static function exportador(string $modulo): ?Exportador
    {
        $clase = self::exportadores()[$modulo] ?? null;

        return $clase === null ? null : new $clase;
    }
}
