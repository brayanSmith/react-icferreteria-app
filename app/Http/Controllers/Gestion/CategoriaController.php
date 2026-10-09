<?php

namespace App\Http\Controllers\Gestion;

use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarCategoriaRequest;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Categorías de productos. Sus subcategorías se administran desde la misma
 * pantalla (ver SubCategoriaController) y comparten el módulo de permisos
 * "categorias".
 */
class CategoriaController extends Controller implements HasMiddleware
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
     * Lista las categorías con sus subcategorías y cuántos productos usa cada una.
     */
    public function index(): Response
    {
        $categorias = Categoria::query()
            ->withCount('productos')
            ->with(['subCategorias' => fn ($query) => $query->withCount('productos')->orderBy('nombre_sub_categoria')])
            ->orderBy('nombre_categoria')
            ->get();

        return Inertia::render('gestion/categorias/index', [
            'categorias' => CategoriaResource::collection($categorias),
        ]);
    }

    /**
     * Crea una categoría.
     */
    public function store(GuardarCategoriaRequest $request): RedirectResponse
    {
        Categoria::create($request->validated());

        $this->avisarExito('Categoría creada.');

        return to_route('gestion.categorias.index');
    }

    /**
     * Actualiza una categoría.
     */
    public function update(GuardarCategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($request->validated());

        $this->avisarExito('Categoría actualizada.');

        return to_route('gestion.categorias.index');
    }

    /**
     * Elimina una categoría, salvo que algún producto la use: la base
     * borraría esos productos en cascada. Sus subcategorías se van con ella.
     */
    public function destroy(Categoria $categoria): RedirectResponse
    {
        if ($categoria->productos()->withTrashed()->exists()) {
            return $this->rechazarEliminacion(
                'No puedes eliminar esta categoría porque hay productos que la usan.',
                'gestion.categorias.index',
            );
        }

        $categoria->delete();

        $this->avisarExito('Categoría eliminada.');

        return to_route('gestion.categorias.index');
    }
}
