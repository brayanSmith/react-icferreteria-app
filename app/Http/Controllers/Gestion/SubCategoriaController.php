<?php

namespace App\Http\Controllers\Gestion;

use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarSubCategoriaRequest;
use App\Models\Categoria;
use App\Models\SubCategoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Subcategorías de una categoría.
 *
 * No tienen listado propio: se muestran dentro de cada categoría. Como la
 * categoría llega por la ruta (y las categorías están limitadas al tenant), una
 * subcategoría de otro tenant nunca se puede alcanzar.
 */
class SubCategoriaController extends Controller implements HasMiddleware
{
    use EsCrudDeGestion;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return self::permisosCrud(Modulo::Categorias);
    }

    /**
     * Crea una subcategoría dentro de la categoría indicada.
     */
    public function store(GuardarSubCategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->subCategorias()->create($request->validated());

        $this->avisarExito('Subcategoría creada.');

        return to_route('gestion.categorias.index');
    }

    /**
     * Actualiza una subcategoría.
     */
    public function update(GuardarSubCategoriaRequest $request, Categoria $categoria, SubCategoria $subCategoria): RedirectResponse
    {
        $subCategoria->update($request->validated());

        $this->avisarExito('Subcategoría actualizada.');

        return to_route('gestion.categorias.index');
    }

    /**
     * Elimina una subcategoría, salvo que algún producto la use.
     */
    public function destroy(Categoria $categoria, SubCategoria $subCategoria): RedirectResponse
    {
        if ($subCategoria->productos()->withTrashed()->exists()) {
            return $this->rechazarEliminacion(
                'No puedes eliminar esta subcategoría porque hay productos que la usan.',
                'gestion.categorias.index',
            );
        }

        $subCategoria->delete();

        $this->avisarExito('Subcategoría eliminada.');

        return to_route('gestion.categorias.index');
    }
}
