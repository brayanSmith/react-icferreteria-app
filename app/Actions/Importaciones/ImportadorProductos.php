<?php

namespace App\Actions\Importaciones;

use App\Enums\Modulo;
use App\Http\Requests\Gestion\GuardarProductoRequest;
use App\Models\Categoria;
use App\Models\Importacion;
use App\Models\Medida;
use App\Models\Producto;
use App\Models\SubCategoria;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Importa productos.
 *
 * La categoría, la subcategoría y la medida se relacionan por su nombre y
 * deben existir; si no, la fila se rechaza. El código lo identifica:
 *
 * - Crear: el sistema genera el código (MP-001, PT-001...) según el tipo de
 *   producto; si el archivo trae uno, se ignora. Un producto con el mismo
 *   nombre y la misma marca que otro ya existente se omite, así que el mismo
 *   archivo se puede importar varias veces sin duplicar.
 * - Actualizar: la fila modifica el producto con ese código, y solo cambia lo
 *   que trae lleno (una celda vacía deja el dato actual). El código no cambia.
 * - Crear y actualizar: con código actualiza; sin código crea.
 */
class ImportadorProductos extends Importador
{
    use InterpretaValores;

    /** Columnas que se copian tal cual al producto. */
    private const DIRECTAS = [
        'categoria_producto', 'nombre_producto', 'descripcion_producto',
        'costo_producto', 'valor_detal_producto', 'valor_mayorista_producto', 'valor_ferretero_producto', 'valor_especial_producto',
        'iva_producto', 'marca', 'codigo_barra', 'alerta_producto', 'ubicacion_producto', 'activo', 'tipo_producto',
        'peso_producto', 'volumen_producto', 'tipo_compra', 'empaquetado_externo', 'empaquetado_interno',
        'referencia_producto', 'codigo_cliente', 'codigo_importado', 'detalles_producto',
    ];

    private const NUMERICAS = [
        'costo_producto', 'valor_detal_producto', 'valor_mayorista_producto', 'valor_ferretero_producto',
        'valor_especial_producto', 'iva_producto', 'alerta_producto', 'peso_producto',
    ];

    /** @var array<string, string> */
    private const TIPOS = [
        'MATERIA_PRIMA' => 'Materia prima',
        'PRODUCTO_TERMINADO' => 'Producto terminado',
        'OTRO' => 'Otro',
        'DESCUENTO' => 'Descuento',
        'GASTOS' => 'Gastos',
    ];

    /** @var array<string, string> */
    private const VOLUMENES = [
        'EXTRA_GRANDE' => 'Extra grande',
        'GRANDE' => 'Grande',
        'MEDIANO' => 'Mediano',
        'PEQUEÑO' => 'Pequeño',
        'EXTRA_PEQUEÑO' => 'Extra pequeño',
    ];

    /** @var array<string, string> */
    private const TIPOS_COMPRA = [
        'NACIONAL' => 'Nacional',
        'IMPORTADO' => 'Importado',
    ];

    /** @var array<string, int>|null */
    private ?array $categorias = null;

    /** @var array<int, array<string, int>> */
    private array $subCategorias = [];

    /** @var array<string, int>|null */
    private ?array $medidas = null;

    /** @var array<string, true>|null Claves "nombre|marca" de los productos que ya existen. */
    private ?array $existentes = null;

    public function modulo(): Modulo
    {
        return Modulo::Productos;
    }

    public function etiqueta(): string
    {
        return 'Productos';
    }

    public function columnas(): array
    {
        return [
            'codigo_producto' => 'Código',
            'categoria_producto' => 'Tipo de producto',
            'nombre_producto' => 'Nombre',
            'descripcion_producto' => 'Descripción',
            'categoria' => 'Categoría',
            'subcategoria' => 'Subcategoría',
            'medida' => 'Medida',
            'costo_producto' => 'Costo',
            'valor_detal_producto' => 'Precio detal',
            'valor_mayorista_producto' => 'Precio mayorista',
            'valor_ferretero_producto' => 'Precio ferretero',
            'valor_especial_producto' => 'Precio especial',
            'iva_producto' => 'IVA (%)',
            'marca' => 'Marca',
            'codigo_barra' => 'Código de barras',
            'alerta_producto' => 'Alerta de stock',
            'ubicacion_producto' => 'Ubicación',
            'activo' => 'Activo',
            'tipo_producto' => 'Tipo',
            'peso_producto' => 'Peso',
            'volumen_producto' => 'Volumen',
            'tipo_compra' => 'Tipo de compra',
            'empaquetado_externo' => 'Empaque externo',
            'empaquetado_interno' => 'Empaque interno',
            'referencia_producto' => 'Referencia',
            'codigo_cliente' => 'Código del cliente',
            'codigo_importado' => 'Código de importación',
            'detalles_producto' => 'Detalles',
        ];
    }

    public function sinonimos(): array
    {
        return [
            'codigo_producto' => ['codigo producto', 'cod', 'sku', 'referencia interna'],
            'categoria_producto' => ['tipo producto', 'clase de producto'],
            'nombre_producto' => ['nombre producto', 'producto', 'articulo'],
            'descripcion_producto' => ['descripcion producto'],
            'categoria' => ['nombre categoria', 'categoria producto', 'linea'],
            'subcategoria' => ['sub categoria', 'sub_categoria', 'nombre subcategoria'],
            'medida' => ['unidad', 'unidad de medida', 'um'],
            'costo_producto' => ['costo producto', 'costo unitario'],
            'valor_detal_producto' => ['precio', 'precio publico', 'valor detal', 'precio al detal'],
            'valor_mayorista_producto' => ['valor mayorista'],
            'valor_ferretero_producto' => ['valor ferretero'],
            'valor_especial_producto' => ['valor especial'],
            'iva_producto' => ['iva', 'impuesto'],
            'codigo_barra' => ['codigo de barra', 'codigo barras', 'ean', 'barras'],
            'alerta_producto' => ['alerta', 'stock minimo', 'minimo'],
            'ubicacion_producto' => ['ubicacion producto'],
            'peso_producto' => ['peso producto'],
        ];
    }

    public function columnaId(): string
    {
        return 'codigo_producto';
    }

    public function identificador(): string
    {
        return 'su código';
    }

    public function columnasRequeridas(string $modo): array
    {
        return $modo === Importacion::MODO_CREAR
            ? ['nombre_producto', 'categoria', 'subcategoria', 'medida']
            : ['codigo_producto'];
    }

    public function normalizar(array $datos): array
    {
        foreach (self::NUMERICAS as $campo) {
            $datos[$campo] = self::numero($datos[$campo] ?? null);
        }

        $datos['categoria_producto'] = self::deLista($datos['categoria_producto'] ?? null, self::TIPOS);
        $datos['volumen_producto'] = self::deLista($datos['volumen_producto'] ?? null, self::VOLUMENES);
        $datos['tipo_compra'] = self::deLista($datos['tipo_compra'] ?? null, self::TIPOS_COMPRA);
        $datos['activo'] = self::booleano($datos['activo'] ?? null);

        return $datos;
    }

    public function reglas(string $modo): array
    {
        $crear = $modo === Importacion::MODO_CREAR;
        $obligatoria = $crear ? 'required' : 'nullable';
        $precio = ['nullable', 'numeric', 'min:0', 'max:99999999'];

        return [
            'codigo_producto' => [$crear ? 'nullable' : 'required', 'string', 'max:255'],
            'categoria_producto' => ['nullable', 'in:'.implode(',', GuardarProductoRequest::CATEGORIAS_PRODUCTO)],
            'nombre_producto' => [$obligatoria, 'string', 'max:255'],
            'descripcion_producto' => ['nullable', 'string', 'max:255'],
            'categoria' => [$obligatoria, 'string', 'max:255'],
            'subcategoria' => [$obligatoria, 'string', 'max:255'],
            'medida' => [$obligatoria, 'string', 'max:255'],
            'costo_producto' => $precio,
            'valor_detal_producto' => $precio,
            'valor_mayorista_producto' => $precio,
            'valor_ferretero_producto' => $precio,
            'valor_especial_producto' => $precio,
            'iva_producto' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'marca' => ['nullable', 'string', 'max:255'],
            'codigo_barra' => ['nullable', 'string', 'max:255'],
            'alerta_producto' => ['nullable', 'integer', 'min:0'],
            'ubicacion_producto' => ['nullable', 'string', 'max:255'],
            'activo' => ['nullable', 'boolean'],
            'tipo_producto' => ['nullable', 'string', 'max:100'],
            'peso_producto' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'volumen_producto' => ['nullable', 'in:'.implode(',', GuardarProductoRequest::VOLUMENES)],
            'tipo_compra' => ['nullable', 'in:'.implode(',', GuardarProductoRequest::TIPOS_COMPRA)],
            'empaquetado_externo' => ['nullable', 'string', 'max:255'],
            'empaquetado_interno' => ['nullable', 'string', 'max:255'],
            'referencia_producto' => ['nullable', 'string', 'max:255'],
            'codigo_cliente' => ['nullable', 'string', 'max:255'],
            'codigo_importado' => ['nullable', 'string', 'max:5000'],
            'detalles_producto' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function guardar(array $datos, string $modo): bool
    {
        return $modo === Importacion::MODO_CREAR
            ? $this->crear($datos)
            : $this->actualizar($datos);
    }

    public function ejemplos(): array
    {
        $ejemplos = [
            ['nombre_producto' => 'Martillo de uña', 'categoria_producto' => 'Producto terminado', 'categoria' => 'Herramientas', 'subcategoria' => 'Manuales', 'medida' => 'Unidad', 'costo_producto' => '12000', 'valor_detal_producto' => '18000', 'marca' => 'Truper', 'activo' => 'Sí'],
            ['nombre_producto' => 'Pintura blanca base', 'categoria_producto' => 'Materia prima', 'categoria' => 'Pinturas', 'subcategoria' => 'Esmaltes', 'medida' => 'Galón', 'costo_producto' => '45000'],
        ];

        return array_map(
            fn (array $ejemplo): array => array_map(fn (string $clave): string => $ejemplo[$clave] ?? '', array_keys($this->columnas())),
            $ejemplos,
        );
    }

    /**
     * @param  array<string, string|null>  $datos
     */
    private function crear(array $datos): bool
    {
        $claveDuplicado = self::claveDeDuplicado($datos['nombre_producto'], $datos['marca']);

        if (isset($this->existentes()[$claveDuplicado])) {
            return false;
        }

        $ids = $this->idsDelCatalogo($datos, null);
        $atributos = array_filter(Arr::only($datos, self::DIRECTAS), fn (?string $valor): bool => $valor !== null);
        $atributos['categoria_producto'] ??= 'PRODUCTO_TERMINADO';
        $atributos['activo'] = ($atributos['activo'] ?? '1') === '1';

        // El consecutivo del código se toma en la misma transacción que el producto, para no dejar huecos.
        DB::transaction(function () use ($atributos, $ids): void {
            $codigo = Producto::siguienteCodigo(Auth::user()->tenant_id, $atributos['categoria_producto']);

            Producto::create([
                ...$atributos,
                ...$ids,
                'codigo_producto' => $codigo,
                'concatenar_codigo_nombre' => "{$codigo} - {$atributos['nombre_producto']}",
            ]);
        });

        $this->existentes[$claveDuplicado] = true;

        return true;
    }

    /**
     * @param  array<string, string|null>  $datos
     */
    private function actualizar(array $datos): bool
    {
        $producto = Producto::query()->where('codigo_producto', $datos['codigo_producto'])->first()
            ?? throw new FilaRechazada("No existe un producto con el código {$datos['codigo_producto']}.");

        $cambios = array_filter(Arr::only($datos, self::DIRECTAS), fn (?string $valor): bool => $valor !== null);
        if (isset($cambios['activo'])) {
            $cambios['activo'] = $cambios['activo'] === '1';
        }

        $cambios = [...$cambios, ...$this->idsDelCatalogo($datos, $producto)];

        // Solo cuenta lo que de verdad es distinto del valor actual.
        $cambios = array_filter($cambios, fn (mixed $nuevo, string $campo): bool => ! self::sonIguales($producto->getAttribute($campo), $nuevo), ARRAY_FILTER_USE_BOTH);

        if ($cambios === []) {
            return false;
        }

        $producto->fill($cambios);

        if (isset($cambios['nombre_producto'])) {
            $producto->concatenar_codigo_nombre = "{$producto->codigo_producto} - {$producto->nombre_producto}";
        }

        $producto->save();

        return true;
    }

    /**
     * Ids de categoría, subcategoría y medida a partir de sus nombres. Solo
     * devuelve los que la fila trae; si no existen, la fila se rechaza.
     *
     * @param  array<string, string|null>  $datos
     * @return array<string, int>
     */
    private function idsDelCatalogo(array $datos, ?Producto $producto): array
    {
        $ids = [];
        $categoriaId = $producto?->categoria_id;

        if ($datos['categoria'] !== null) {
            $categoriaId = $this->categorias()[mb_strtolower($datos['categoria'])]
                ?? throw new FilaRechazada("La categoría {$datos['categoria']} no existe.");
            $ids['categoria_id'] = $categoriaId;

            if ($producto !== null && $categoriaId !== $producto->categoria_id && $datos['subcategoria'] === null) {
                throw new FilaRechazada('Al cambiar la categoría indica también la subcategoría.');
            }
        }

        if ($datos['subcategoria'] !== null) {
            $ids['sub_categoria_id'] = $this->subCategoriasDe($categoriaId)[mb_strtolower($datos['subcategoria'])]
                ?? throw new FilaRechazada("La subcategoría {$datos['subcategoria']} no existe en esa categoría.");
        }

        if ($datos['medida'] !== null) {
            $ids['medida_id'] = $this->medidas()[mb_strtolower($datos['medida'])]
                ?? throw new FilaRechazada("La medida {$datos['medida']} no existe.");
        }

        return $ids;
    }

    /**
     * @return array<string, int>
     */
    private function categorias(): array
    {
        return $this->categorias ??= Categoria::query()
            ->pluck('id', 'nombre_categoria')
            ->mapWithKeys(fn (int $id, string $nombre): array => [mb_strtolower($nombre) => $id])
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function subCategoriasDe(int $categoriaId): array
    {
        return $this->subCategorias[$categoriaId] ??= SubCategoria::query()
            ->where('categoria_id', $categoriaId)
            ->pluck('id', 'nombre_sub_categoria')
            ->mapWithKeys(fn (int $id, string $nombre): array => [mb_strtolower($nombre) => $id])
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function medidas(): array
    {
        return $this->medidas ??= Medida::query()
            ->pluck('id', 'nombre_medida')
            ->mapWithKeys(fn (int $id, string $nombre): array => [mb_strtolower($nombre) => $id])
            ->all();
    }

    /**
     * @return array<string, true>
     */
    private function existentes(): array
    {
        if ($this->existentes === null) {
            $this->existentes = [];

            foreach (Producto::query()->select(['nombre_producto', 'marca'])->cursor() as $producto) {
                $this->existentes[self::claveDeDuplicado($producto->nombre_producto, $producto->marca)] = true;
            }
        }

        return $this->existentes;
    }

    private static function claveDeDuplicado(string $nombre, ?string $marca): string
    {
        return mb_strtolower($nombre).'|'.mb_strtolower($marca ?? '');
    }
}
