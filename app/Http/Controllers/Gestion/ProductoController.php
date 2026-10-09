<?php

namespace App\Http\Controllers\Gestion;

use App\Actions\Archivos\GuardarArchivoPublico;
use App\Enums\Modulo;
use App\Http\Controllers\Concerns\EsCrudDeGestion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gestion\GuardarProductoRequest;
use App\Http\Resources\ProductoResource;
use App\Models\Categoria;
use App\Models\Medida;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catálogo de productos del tenant.
 *
 * Se listan con búsqueda y paginación, se eliminan de forma lógica y se pueden
 * restaurar. El formulario necesita los catálogos de medidas y
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
     * barras, por tipo de producto (las pestañas) y, opcionalmente, solo los
     * eliminados. Devuelve además cuántos productos hay en cada pestaña.
     */
    public function index(Request $request): Response
    {
        $tipo = $request->query('tipo');

        $filtros = [
            'buscar' => (string) $request->query('buscar', ''),
            'eliminados' => $request->boolean('eliminados'),
            // Solo se aceptan los tipos conocidos; cualquier otro valor equivale a "todos".
            'tipo' => in_array($tipo, GuardarProductoRequest::CATEGORIAS_PRODUCTO, true) ? $tipo : null,
        ];

        // Base común (búsqueda y eliminados) que comparten el listado y el conteo de las pestañas.
        $base = fn () => Producto::query()
            ->when($filtros['eliminados'], fn ($query) => $query->onlyTrashed())
            ->when($filtros['buscar'] !== '', fn ($query) => $query->where(function ($query) use ($filtros): void {
                $query->where('codigo_producto', 'like', "%{$filtros['buscar']}%")
                    ->orWhere('nombre_producto', 'like', "%{$filtros['buscar']}%")
                    ->orWhere('marca', 'like', "%{$filtros['buscar']}%")
                    ->orWhere('codigo_barra', 'like', "%{$filtros['buscar']}%");
            }));

        $productos = $base()
            ->with(['medida:id,nombre_medida', 'categoria:id,nombre_categoria', 'subCategoria:id,nombre_sub_categoria'])
            ->when($filtros['tipo'], fn ($query, $tipoElegido) => $query->where('categoria_producto', $tipoElegido))
            ->orderBy('nombre_producto')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('gestion/productos/index', [
            'productos' => ProductoResource::collection($productos),
            'filtros' => $filtros,
            'conteoPorTipo' => $base()
                ->selectRaw('categoria_producto, count(*) as total')
                ->groupBy('categoria_producto')
                ->pluck('total', 'categoria_producto'),
            ...$this->catalogosDelFormulario(),
        ]);
    }

    /**
     * Crea un producto. El código se genera solo según el tipo de producto
     * (MP-001, PT-001...) y el texto "código - nombre" se arma a partir de él.
     */
    public function store(GuardarProductoRequest $request, GuardarArchivoPublico $archivos): RedirectResponse
    {
        // Todo en una transacción: si el guardado falla, el consecutivo se devuelve.
        DB::transaction(function () use ($request, $archivos): void {
            $codigo = Producto::siguienteCodigo($request->user()->tenant_id, $request->validated('categoria_producto'));

            Producto::create($this->datosParaGuardar($request, $archivos, $codigo));
        });

        $this->avisarExito('Producto creado.');

        return to_route('gestion.productos.index');
    }

    /**
     * Actualiza un producto; reemplaza o quita su imagen según lo enviado.
     */
    public function update(GuardarProductoRequest $request, Producto $producto, GuardarArchivoPublico $archivos): RedirectResponse
    {
        // El código no cambia nunca: identifica al producto aunque cambie su tipo o su nombre.
        $producto->update($this->datosParaGuardar($request, $archivos, $producto->codigo_producto, $producto));

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
     * final de la imagen y fija el código y el texto "código - nombre".
     *
     * @return array<string, mixed>
     */
    private function datosParaGuardar(GuardarProductoRequest $request, GuardarArchivoPublico $archivos, string $codigo, ?Producto $producto = null): array
    {
        $datos = Arr::except($request->validated(), ['imagen', 'quitar_imagen']);

        $datos['imagen_producto'] = $archivos->reemplazar(
            $producto?->imagen_producto,
            $request->file('imagen'),
            $request->boolean('quitar_imagen'),
            "productos/{$request->user()->tenant_id}",
        );
        $datos['codigo_producto'] = $codigo;
        $datos['concatenar_codigo_nombre'] = "{$codigo} - {$datos['nombre_producto']}";

        return $datos;
    }
}
