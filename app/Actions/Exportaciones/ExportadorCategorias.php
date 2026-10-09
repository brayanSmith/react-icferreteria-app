<?php

namespace App\Actions\Exportaciones;

use App\Enums\Modulo;
use App\Models\Categoria;

/**
 * Exporta categorías y subcategorías con las mismas columnas que acepta la
 * importación (incluidos los IDs), así que un archivo exportado se puede
 * editar y volver a importar para actualizar nombres. Una fila por
 * subcategoría; las categorías sin subcategorías llevan vacías las dos
 * últimas columnas.
 */
class ExportadorCategorias extends Exportador
{
    public function modulo(): Modulo
    {
        return Modulo::Categorias;
    }

    public function titulo(): string
    {
        return 'Categorías y subcategorías';
    }

    public function encabezados(): array
    {
        return ['ID categoría', 'Categoría', 'ID subcategoría', 'Subcategoría'];
    }

    public function filas(): iterable
    {
        $categorias = Categoria::query()
            ->with(['subCategorias' => fn ($query) => $query->orderBy('nombre_sub_categoria')])
            ->orderBy('nombre_categoria')
            ->lazy(500);

        foreach ($categorias as $categoria) {
            if ($categoria->subCategorias->isEmpty()) {
                yield [$categoria->id, $categoria->nombre_categoria, '', ''];

                continue;
            }

            foreach ($categoria->subCategorias as $subCategoria) {
                yield [$categoria->id, $categoria->nombre_categoria, $subCategoria->id, $subCategoria->nombre_sub_categoria];
            }
        }
    }
}
