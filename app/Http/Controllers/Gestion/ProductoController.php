<?php

namespace App\Http\Controllers\Gestion;

use App\Actions\Archivos\GuardarArchivoPublico;
use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarProductoRequest;
use App\Http\Resources\ProductoResource;
use App\Models\Bodega;
use App\Models\Categoria;
use App\Models\Medida;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catálogo de productos del tenant.
 *
 * Se listan con búsqueda y paginación, se eliminan de forma lógica y se pueden
 * restaurar. El formulario necesita los catálogos de medidas, bodegas y
 * categorías (con sus subcategorías), que se envían junto al listado.
 */
class ProductoController extends Controller implements HasMiddleware
{
    use EsCrudDeGestion;

    /**
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return self::permisosCrud(Modulo::Productos);
    }

    /**
     * Lista los productos, filtrando por código, nombre, marca o código de
     * barras, y opcionalmente solo los eliminados.
     */
    public function index(Request $request): Response
    {
        $filtros = [
            'buscar' => (string) $request->query('buscar', ''),
            'eliminados' => $request->boolean('eliminados'),
        ];

        $productos = Producto::query()
            ->with(['medida:id,nombre_medida', 'bodega:id,nombre_bodega', 'categoria:id,nombre_categoria', 'subCategoria:id,nombre_sub_categoria'])
            ->when($filtros['eliminados'], fn ($query) => $query->onlyTrashed())
            ->when($filtros['buscar'] !== '', fn ($query) => $query->where(function ($query) use ($filtros): void {
                $query->where('codigo_producto', 'like', "%{$filtros['buscar']}%")
                    ->orWhere('nombre_producto', 'like', "%{$filtros['buscar']}%")
                    ->orWhere('marca', 'like', "%{$filtros['buscar']}%")
                    ->orWhere('codigo_barra', 'like', "%{$filtros['buscar']}%");
            }))
            ->orderBy('nombre_producto')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('gestion/productos/index', [
            'productos' => ProductoResource::collection($productos),
            'filtros' => $filtros,
            ...$this->catalogosDelFormulario(),
        ]);
    }

    /**
     * Crea un producto. El texto "código - nombre" se arma solo.
     */
    public function store(GuardarProductoRequest $request, GuardarArchivoPublico $archivos): RedirectResponse
    {
        Producto::create($this->datosParaGuardar($request, $archivos));

        $this->avisarExito('Producto creado.');

        return to_route('gestion.productos.index');
    }

    /**
     * Actualiza un producto; reemplaza o quita su imagen según lo enviado.
     */
    public function update(GuardarProductoRequest $request, Producto $producto, GuardarArchivoPublico $archivos): RedirectResponse
    {
        $producto->update($this->datosParaGuardar($request, $archivos, $producto));

        $this->avisarExito('Producto actualizado.');

        return to_route('gestion.productos.index');
    }

    /**
     * Elimina (de forma lógica) un producto. Su historial en pedidos y
     * compras se conserva.
     */
    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->delete();

        $this->avisarExito('Producto eliminado.');

        return to_route('gestion.productos.index');
    }

    /**
     * Restaura un producto eliminado.
     */
    public function restore(Producto $producto): RedirectResponse
    {
        $producto->restore();

        $this->avisarExito('Producto restaurado.');

        return to_route('gestion.productos.index');
    }

    /**
     * Catálogos que alimentan los selects del formulario. Las categorías
     * llevan sus subcategorías para que el select dependiente se filtre en
     * el navegador sin pedir nada más.
     *
     * @return array<string, mixed>
     */
    private function catalogosDelFormulario(): array
    {
        return [
            'medidas' => Medida::query()->orderBy('nombre_medida')->get(['id', 'nombre_medida']),
            'bodegas' => Bodega::query()->orderBy('nombre_bodega')->get(['id', 'nombre_bodega']),
            'categorias' => Categoria::query()
                ->with(['subCategorias' => fn ($query) => $query->orderBy('nombre_sub_categoria')->select(['id', 'categoria_id', 'nombre_sub_categoria'])])
                ->orderBy('nombre_categoria')
                ->get(['id', 'nombre_categoria'])
                ->map(fn (Categoria $categoria): array => [
                    'id' => $categoria->id,
                    'nombre_categoria' => $categoria->nombre_categoria,
                    'sub_categorias' => $categoria->subCategorias->map(fn ($sub): array => [
                        'id' => $sub->id,
                        'nombre_sub_categoria' => $sub->nombre_sub_categoria,
                    ])->all(),
                ])
                ->all(),
        ];
    }

    /**
     * Separa los campos del producto de los de la imagen, resuelve la ruta
     * final de la imagen y arma el texto "código - nombre".
     *
     * @return array<string, mixed>
     */
    private function datosParaGuardar(GuardarProductoRequest $request, GuardarArchivoPublico $archivos, ?Producto $producto = null): array
    {
        $datos = Arr::except($request->validated(), ['imagen', 'quitar_imagen']);

        $datos['imagen_producto'] = $archivos->reemplazar(
            $producto?->imagen_producto,
            $request->file('imagen'),
            $request->boolean('quitar_imagen'),
            "productos/{$request->user()->tenant_id}",
        );
        $datos['concatenar_codigo_nombre'] = "{$datos['codigo_producto']} - {$datos['nombre_producto']}";

        return $datos;
    }
}
