<?php

namespace App\Actions\Importaciones;

use App\Actions\Stock\MoverStock;
use App\Enums\Modulo;
use App\Models\Bodega;
use App\Models\DetalleStock;
use App\Models\Importacion;
use App\Models\Producto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Importa movimientos de inventario: ingresos y salidas de un producto en una
 * bodega, registrados como si se hubieran hecho a mano desde Stock por bodega.
 *
 * Un movimiento no tiene nada que lo identifique después, así que este módulo
 * solo crea. Por eso importar dos veces el mismo archivo duplica los
 * movimientos; la pantalla lo advierte.
 */
class ImportadorMovimientosStock extends Importador
{
    use InterpretaValores;

    private const TIPOS = [
        DetalleStock::INGRESO => 'Ingreso',
        DetalleStock::SALIDA => 'Salida',
    ];

    /** @var array<string, int>|null */
    private ?array $productos = null;

    /** @var array<string, int>|null */
    private ?array $bodegas = null;

    public function modulo(): Modulo
    {
        return Modulo::StockBodegas;
    }

    public function etiqueta(): string
    {
        return 'Movimientos de inventario';
    }

    public function columnas(): array
    {
        return [
            'producto' => 'Código del producto',
            'bodega' => 'Bodega',
            'tipo' => 'Tipo (ingreso o salida)',
            'cantidad' => 'Cantidad',
            'motivo' => 'Motivo',
        ];
    }

    public function sinonimos(): array
    {
        return [
            'producto' => ['codigo', 'codigo producto', 'cod', 'sku', 'referencia'],
            'bodega' => ['almacen', 'deposito', 'nombre bodega'],
            'tipo' => ['tipo de movimiento', 'tipo movimiento', 'movimiento', 'ingreso o salida', 'entrada o salida'],
            'cantidad' => ['unidades', 'cant'],
            'motivo' => ['descripcion', 'observacion', 'observaciones', 'detalle', 'concepto', 'nota'],
        ];
    }

    public function modosPermitidos(): array
    {
        return [Importacion::MODO_CREAR];
    }

    public function aviso(): ?string
    {
        return 'Cada fila registra un movimiento nuevo: si importas el mismo archivo dos veces, los movimientos se duplican. Las salidas se rechazan si superan lo que hay en la bodega.';
    }

    /**
     * Sin uso: este módulo solo crea y sus filas no se pueden identificar después.
     */
    public function columnaId(): string
    {
        return 'producto';
    }

    public function columnasRequeridas(string $modo): array
    {
        return ['producto', 'bodega', 'tipo', 'cantidad'];
    }

    public function normalizar(array $datos): array
    {
        $datos['cantidad'] = self::numero($datos['cantidad'] ?? null);
        $datos['tipo'] = self::deLista($datos['tipo'] ?? null, self::TIPOS, [
            'entrada' => DetalleStock::INGRESO,
            'entra' => DetalleStock::INGRESO,
            'ingresa' => DetalleStock::INGRESO,
            'sale' => DetalleStock::SALIDA,
            'egreso' => DetalleStock::SALIDA,
        ]);

        return $datos;
    }

    public function reglas(string $modo): array
    {
        return [
            'producto' => ['required', 'string', 'max:255'],
            'bodega' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'in:'.implode(',', DetalleStock::TIPOS)],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function guardar(array $datos, string $modo): bool
    {
        $productoId = $this->productosDelTenant()[mb_strtolower($datos['producto'])]
            ?? throw new FilaRechazada("No existe un producto con código {$datos['producto']}.");
        $bodegaId = $this->bodegasDelTenant()[mb_strtolower($datos['bodega'])]
            ?? throw new FilaRechazada("La bodega {$datos['bodega']} no existe.");

        try {
            app(MoverStock::class)->registrar(
                productoId: $productoId,
                bodegaId: $bodegaId,
                usuarioId: Auth::id(),
                tipo: $datos['tipo'],
                cantidad: (float) $datos['cantidad'],
                descripcion: $datos['motivo'] ?? 'Importación de movimientos',
            );
        } catch (ValidationException $e) {
            throw new FilaRechazada("La salida de {$datos['producto']} en {$datos['bodega']} supera lo que hay en la bodega.", previous: $e);
        }

        return true;
    }

    public function ejemplos(): array
    {
        return [
            ['MP-001', 'Principal', 'Ingreso', '50', 'Compra de mercancía'],
            ['MP-001', 'Principal', 'Salida', '5,5', 'Merma'],
        ];
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
