<?php

namespace App\Http\Requests\Gestion;

use App\Models\Producto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el alta y la edición de un producto.
 *
 * La categoría y la subcategoría son dependientes: la subcategoría elegida
 * debe pertenecer a la categoría elegida. Todas las claves foráneas se
 * comprueban contra el tenant del usuario, para que no se pueda enlazar con
 * datos de otra empresa.
 */
class GuardarProductoRequest extends FormRequest
{
    public const CATEGORIAS_PRODUCTO = ['MATERIA_PRIMA', 'PRODUCTO_TERMINADO', 'OTRO', 'DESCUENTO', 'GASTOS'];

    public const VOLUMENES = ['EXTRA_GRANDE', 'GRANDE', 'MEDIANO', 'PEQUEÑO', 'EXTRA_PEQUEÑO'];

    public const TIPOS_COMPRA = ['NACIONAL', 'IMPORTADO'];

    /**
     * Los casilleros sin marcar no llegan en un formulario HTML, y los precios,
     * el stock inicial y la alerta vacíos se tratan como cero.
     */
    protected function prepareForValidation(): void
    {
        $precios = ['costo_producto', 'valor_detal_producto', 'valor_mayorista_producto', 'valor_ferretero_producto', 'valor_especial_producto'];

        $this->merge([
            'activo' => $this->boolean('activo'),
            'quitar_imagen' => $this->boolean('quitar_imagen'),
            // Estas columnas no aceptan nulo en la base: vacío significa cero.
            'alerta_producto' => $this->input('alerta_producto') ?: 0,
            ...collect($precios)->mapWithKeys(fn (string $campo): array => [$campo => $this->input($campo) ?: 0])->all(),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'categoria_producto' => ['required', Rule::in(self::CATEGORIAS_PRODUCTO)],
            'nombre_producto' => ['required', 'string', 'max:255'],
            'descripcion_producto' => ['nullable', 'string', 'max:255'],

            'costo_producto' => ['numeric', 'min:0', 'max:99999999'],
            'valor_detal_producto' => ['numeric', 'min:0', 'max:99999999'],
            'valor_mayorista_producto' => ['numeric', 'min:0', 'max:99999999'],
            'valor_ferretero_producto' => ['numeric', 'min:0', 'max:99999999'],
            'valor_especial_producto' => ['numeric', 'min:0', 'max:99999999'],
            'iva_producto' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'medida_id' => ['required', Rule::exists('medidas', 'id')->where('tenant_id', $tenantId)],
            'categoria_id' => ['required', Rule::exists('categorias', 'id')->where('tenant_id', $tenantId)],
            // Selects dependientes: la subcategoría debe ser de la categoría elegida.
            'sub_categoria_id' => [
                'required',
                Rule::exists('sub_categorias', 'id')->where('categoria_id', $this->input('categoria_id')),
            ],

            'alerta_producto' => ['nullable', 'integer', 'min:0'],
            'activo' => ['boolean'],
            'tipo_producto' => ['nullable', 'string', 'max:100'],
            'peso_producto' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'ubicacion_producto' => ['nullable', 'string', 'max:255'],
            'empaquetado_externo' => ['nullable', 'string', 'max:255'],
            'empaquetado_interno' => ['nullable', 'string', 'max:255'],
            'referencia_producto' => ['nullable', 'string', 'max:255'],
            'codigo_cliente' => ['nullable', 'string', 'max:255'],
            'volumen_producto' => ['nullable', Rule::in(self::VOLUMENES)],
            'tipo_compra' => ['nullable', Rule::in(self::TIPOS_COMPRA)],
            'codigo_barra' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'detalles_producto' => ['nullable', 'string', 'max:5000'],
            'codigo_importado' => ['nullable', 'string', 'max:5000'],

            'imagen' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'quitar_imagen' => ['boolean'],
        ];
    }
}
