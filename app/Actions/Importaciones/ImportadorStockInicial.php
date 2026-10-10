<?php

namespace App\Actions\Importaciones;

use App\Actions\Stock\SincronizarStockInicial;
use App\Enums\Modulo;
use App\Models\Bodega;
use App\Models\Importacion;
use App\Models\Producto;
use App\Models\StockInicial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Importa el stock inicial: la cantidad con la que arranca cada producto en
 * cada bodega. Cada fila genera su ingreso en los movimientos de la bodega,
 * igual que al registrarlo desde la pantalla.
 *
 * El producto (por su código) y la bodega (por su nombre) identifican el
 * registro, así que no hace falta un ID:
 *
 * - Crear: la fila registra el stock inicial; si ya existe, se omite. Uno
 *   eliminado se rechaza: hay que restaurarlo.
 * - Actualizar: cambia la cantidad del stock inicial que ya existe. No se
 *   puede dejar por debajo de lo que ya salió de la bodega.
 * - Crear y actualizar: si ya existe actualiza; si no, crea.
 */
class ImportadorStockInicial extends Importador
{
    use InterpretaValores;

    /** @var array<string, int>|null */
    private ?array $productos = null;

    /** @var array<string, int>|null */
    private ?array $bodegas = null;

    public function modulo(): Modulo
    {
        return Modulo::StockInicial;
    }

    public function etiqueta(): string
    {
        return 'Stock inicial';
    }

    public function columnas(): array
    {
        return [
            'producto' => 'Código del producto',
            'bodega' => 'Bodega',
            'cantidad' => 'Cantidad',
        ];
    }

    public function sinonimos(): array
    {
        return [
            'producto' => ['codigo', 'codigo producto', 'cod', 'sku', 'referencia'],
            'bodega' => ['almacen', 'deposito', 'nombre bodega'],
            'cantidad' => ['stock', 'existencias', 'cantidad inicial', 'unidades', 'saldo'],
        ];
    }

    public function identificador(): string
    {
        return 'su producto y su bodega';
    }

    /**
     * Sin ID: el producto y la bodega lo identifican, así que toda la fila es necesaria.
     */
    public function columnaId(): string
    {
        return 'producto';
    }

    public function columnasRequeridas(string $modo): array
    {
        return ['producto', 'bodega', 'cantidad'];
    }

    /**
     * En "ambos", el producto y la bodega deciden: si ya hay un stock inicial
     * (también si está eliminado, para avisar) se actualiza; si no, se crea.
     */
    public function modoDeFila(array $datos, string $modo): string
    {
        if ($modo !== Importacion::MODO_AMBOS) {
            return $modo;
        }

        $existente = $this->buscar($datos['producto'], $datos['bodega'], conEliminados: true);

        return $existente === null ? Importacion::MODO_CREAR : Importacion::MODO_ACTUALIZAR;
    }

    public function normalizar(array $datos): array
    {
        $datos['cantidad'] = self::numero($datos['cantidad'] ?? null);

        return $datos;
    }

    public function reglas(string $modo): array
    {
        return [
            'producto' => ['required', 'string', 'max:255'],
            'bodega' => ['required', 'string', 'max:255'],
            'cantidad' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    public function guardar(array $datos, string $modo): bool
    {
        $productoId = $this->idDelProducto($datos['producto']);
        $bodegaId = $this->idDeLaBodega($datos['bodega']);
        $cantidad = round((float) $datos['cantidad'], 2);

        $existente = StockInicial::withTrashed()
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->first();

        if ($modo === Importacion::MODO_CREAR) {
            return $this->crear($existente, $productoId, $bodegaId, $cantidad, $datos);
        }

        return $this->actualizar($existente, $cantidad, $datos);
    }

    public function ejemplos(): array
    {
        return [
            ['MP-001', 'Principal', '120'],
            ['PT-001', 'Principal', '35,5'],
        ];
    }

    /**
     * @param  array<string, string|null>  $datos
     */
    private function crear(?StockInicial $existente, int $productoId, int $bodegaId, float $cantidad, array $datos): bool
    {
        if ($existente !== null) {
            if ($existente->trashed()) {
                throw new FilaRechazada("El stock inicial de {$datos['producto']} en {$datos['bodega']} está eliminado. Restáuralo en lugar de importarlo.");
            }

            return false;
        }

        $this->guardarYSincronizar(fn (): StockInicial => StockInicial::create([
            'producto_id' => $productoId,
            'bodega_id' => $bodegaId,
            'cantidad' => $cantidad,
        ]));

        return true;
    }

    /**
     * @param  array<string, string|null>  $datos
     */
    private function actualizar(?StockInicial $existente, float $cantidad, array $datos): bool
    {
        if ($existente === null) {
            throw new FilaRechazada("No hay un stock inicial de {$datos['producto']} en {$datos['bodega']}.");
        }

        if ($existente->trashed()) {
            throw new FilaRechazada("El stock inicial de {$datos['producto']} en {$datos['bodega']} está eliminado. Restáuralo para poder actualizarlo.");
        }

        if (round((float) $existente->cantidad, 2) === $cantidad) {
            return false;
        }

        $this->guardarYSincronizar(function () use ($existente, $cantidad): StockInicial {
            $existente->update(['cantidad' => $cantidad]);

            return $existente;
        });

        return true;
    }

    /**
     * Guarda el stock inicial y su ingreso en la bodega en una sola transacción:
     * si el inventario lo rechaza (dejaría el stock en negativo), no se guarda nada.
     *
     * @param  callable(): StockInicial  $guardar
     */
    private function guardarYSincronizar(callable $guardar): void
    {
        try {
            DB::transaction(function () use ($guardar): void {
                app(SincronizarStockInicial::class)->handle($guardar(), Auth::id());
            });
        } catch (ValidationException $e) {
            throw new FilaRechazada('No se puede reducir el stock inicial: esa mercancía ya tuvo salidas y el stock quedaría en negativo.', previous: $e);
        }
    }

    private function buscar(?string $producto, ?string $bodega, bool $conEliminados = false): ?StockInicial
    {
        if ($producto === null || $bodega === null) {
            return null;
        }

        $productoId = $this->productosDelTenant()[mb_strtolower($producto)] ?? null;
        $bodegaId = $this->bodegasDelTenant()[mb_strtolower($bodega)] ?? null;

        if ($productoId === null || $bodegaId === null) {
            return null;
        }

        return StockInicial::query()
            ->when($conEliminados, fn ($query) => $query->withTrashed())
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->first();
    }

    private function idDelProducto(string $codigo): int
    {
        return $this->productosDelTenant()[mb_strtolower($codigo)]
            ?? throw new FilaRechazada("No existe un producto con código {$codigo}.");
    }

    private function idDeLaBodega(string $nombre): int
    {
        return $this->bodegasDelTenant()[mb_strtolower($nombre)]
            ?? throw new FilaRechazada("La bodega {$nombre} no existe.");
    }

    /**
     * Productos activos de la empresa por su código, en minúsculas.
     *
     * @return array<string, int>
     */
    private function productosDelTenant(): array
    {
        return $this->productos ??= Producto::query()
            ->pluck('id', 'codigo_producto')
            ->mapWithKeys(fn (int $id, string $codigo): array => [mb_strtolower($codigo) => $id])
            ->all();
    }

    /**
     * Bodegas activas de la empresa por su nombre, en minúsculas.
     *
     * @return array<string, int>
     */
    private function bodegasDelTenant(): array
    {
        return $this->bodegas ??= Bodega::query()
            ->pluck('id', 'nombre_bodega')
            ->mapWithKeys(fn (int $id, string $nombre): array => [mb_strtolower($nombre) => $id])
            ->all();
    }
}
