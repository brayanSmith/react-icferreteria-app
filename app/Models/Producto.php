<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'categoria_producto',
        'codigo_producto',
        'nombre_producto',
        'descripcion_producto',
        'categoria_id',
        'sub_categoria_id',
        'costo_producto',
        'valor_detal_producto',
        'valor_mayorista_producto',
        'valor_ferretero_producto',
        'valor_especial_producto',
        'imagen_producto',
        'bodega_id',
        'stock_inicial',
        'entradas',
        'salidas',
        'activo',
        'tipo_producto',
        'peso_producto',
        'ubicacion_producto',
        'alerta_producto',
        'empaquetado_externo',
        'empaquetado_interno',
        'referencia_producto',
        'codigo_cliente',
        'volumen_producto',
        'iva_producto',
        'tipo_compra',
        'medida_id',
        'codigo_barra',
        'marca',
        'detalles_producto',
        'concatenar_codigo_nombre',
        'codigo_importado',

    ];
    /*public function detalleCompras()
    {
        return $this->hasMany(DetalleCompra::class);
    }*/

    public function detallePedidos()
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function detalleFormulas()
    {
        return $this->hasMany(DetalleFormula::class);
    }

    public function medida()
    {
        return $this->belongsTo(Medida::class, 'medida_id');
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function subCategoria()
    {
        return $this->belongsTo(SubCategoria::class, 'sub_categoria_id');
    }

    public function detalleProducciones()
    {
        return $this->hasMany(DetalleProduccion::class);
    }

    public function traslados()
    {
        return $this->hasMany(Traslado::class);
    }

    public function stockBodegas()
    {
        return $this->hasMany(StockBodega::class);
    }

    public function stockBodegaPrincipal()
    {
        return $this->hasOne(StockBodega::class)
            ->where('bodega_id', 1); // Suponiendo que la bodega principal tiene ID 1
    }

    public function getPrecioPorTipo(string $tipo): float
    {
        return match ($tipo) {
            'MAYORISTA' => $this->valor_mayorista_producto ?? 0,
            'FERRETERO' => $this->valor_ferretero_producto ?? 0,
            'ESPECIAL' => $this->valor_especial_producto ?? 0,
            default     => $this->valor_detal_producto ?? 0,
        };
    }

    public function enStock(float|int $cantidad): bool
    {
        // cantidad inválida
        if ($cantidad <= 0) {
            return false;
        }

        $stock = (float) ($this->stock ?? 0);

        return $stock >= (float) $cantidad;
    }
    //esta funcion calcula el stock actual restando las salidas a las entradas
    public function getStockAttribute($value): float
    {
        $entradas = (float) ($this->entradas ?? 0);
        $salidas = (float) ($this->salidas ?? 0);

        return $entradas - $salidas;
    }


    /**
     * The "booted" method of the model.
     */
    /*protected static function booted(): void
    {
        // Evento que se ejecuta antes de crear un producto
        static::creating(function (Producto $producto) {
            $producto->concatenar_codigo_nombre = "{$producto->codigo_producto} - {$producto->nombre_producto}";
        });

        // Evento que se ejecuta antes de actualizar un producto
        static::updating(function (Producto $producto) {
            // Solo actualizar si cambió el código o el nombre
            if ($producto->isDirty(['codigo_producto', 'nombre_producto'])) {
                $producto->concatenar_codigo_nombre = "{$producto->codigo_producto} - {$producto->nombre_producto}";
            }
        });
    }*/
}
