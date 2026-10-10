<?php

namespace App\Actions\Exportaciones;

use App\Enums\Modulo;
use App\Models\Producto;

/**
 * Exporta los productos con las mismas columnas que acepta la importación,
 * así que el archivo se puede editar y volver a importar para actualizar
 * productos (el código los identifica). Los tipos salen con su nombre legible.
 */
class ExportadorProductos extends Exportador
{
    private const TIPOS = [
        'MATERIA_PRIMA' => 'Materia prima',
        'PRODUCTO_TERMINADO' => 'Producto terminado',
        'OTRO' => 'Otro',
        'DESCUENTO' => 'Descuento',
        'GASTOS' => 'Gastos',
    ];

    private const VOLUMENES = [
        'EXTRA_GRANDE' => 'Extra grande',
        'GRANDE' => 'Grande',
        'MEDIANO' => 'Mediano',
        'PEQUEÑO' => 'Pequeño',
        'EXTRA_PEQUEÑO' => 'Extra pequeño',
    ];

    private const TIPOS_COMPRA = [
        'NACIONAL' => 'Nacional',
        'IMPORTADO' => 'Importado',
    ];

    public function modulo(): Modulo
    {
        return Modulo::Productos;
    }

    public function titulo(): string
    {
        return 'Productos';
    }

    public function encabezados(): array
    {
        return [
            'Código', 'Tipo de producto', 'Nombre', 'Descripción', 'Categoría', 'Subcategoría', 'Medida',
            'Costo', 'Precio detal', 'Precio mayorista', 'Precio ferretero', 'Precio especial', 'IVA (%)',
            'Marca', 'Código de barras', 'Alerta de stock', 'Ubicación', 'Activo', 'Tipo', 'Peso', 'Volumen',
            'Tipo de compra', 'Empaque externo', 'Empaque interno', 'Referencia', 'Código del cliente',
            'Código de importación', 'Detalles',
        ];
    }

    public function filas(): iterable
    {
        $productos = Producto::query()
            ->with(['categoria:id,nombre_categoria', 'subCategoria:id,nombre_sub_categoria', 'medida:id,nombre_medida'])
            ->orderBy('codigo_producto')
            ->lazy(500);

        foreach ($productos as $producto) {
            yield [
                $producto->codigo_producto,
                self::TIPOS[$producto->categoria_producto] ?? $producto->categoria_producto,
                $producto->nombre_producto,
                $producto->descripcion_producto ?? '',
                $producto->categoria?->nombre_categoria ?? '',
                $producto->subCategoria?->nombre_sub_categoria ?? '',
                $producto->medida?->nombre_medida ?? '',
                $producto->costo_producto,
                $producto->valor_detal_producto,
                $producto->valor_mayorista_producto,
                $producto->valor_ferretero_producto,
                $producto->valor_especial_producto,
                $producto->iva_producto,
                $producto->marca ?? '',
                $producto->codigo_barra ?? '',
                $producto->alerta_producto,
                $producto->ubicacion_producto ?? '',
                $producto->activo ? 'Sí' : 'No',
                $producto->tipo_producto ?? '',
                $producto->peso_producto ?? '',
                self::VOLUMENES[$producto->volumen_producto] ?? $producto->volumen_producto ?? '',
                self::TIPOS_COMPRA[$producto->tipo_compra] ?? $producto->tipo_compra ?? '',
                $producto->empaquetado_externo ?? '',
                $producto->empaquetado_interno ?? '',
                $producto->referencia_producto ?? '',
                $producto->codigo_cliente ?? '',
                $producto->codigo_importado ?? '',
                $producto->detalles_producto ?? '',
            ];
        }
    }
}
