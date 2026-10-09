import { useState } from 'react';
import type { ReactNode } from 'react';
import RadioGroupField from '@/components/crud/radio-group-field';
import SelectField from '@/components/crud/select-field';
import TextField from '@/components/crud/text-field';
import ImageField from '@/components/image-field';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    CATEGORIAS_PRODUCTO,
    TIPOS_COMPRA,
    VOLUMENES_PRODUCTO,
} from '@/lib/catalogos';
import type { CategoriaOpcion, OpcionSelect, Producto } from '@/types';

/** Bloque con título dentro del formulario. */
function Seccion({
    titulo,
    children,
}: {
    titulo: string;
    children: ReactNode;
}) {
    return (
        <fieldset className="space-y-4 rounded-lg border p-4">
            <legend className="px-2 text-sm font-medium">{titulo}</legend>
            {children}
        </fieldset>
    );
}

type ProductoFieldsProps = {
    producto?: Producto;
    errors: Record<string, string>;
    medidas: OpcionSelect[];
    categorias: CategoriaOpcion[];
    /** Tipo de producto con el que arranca un producto nuevo (por ejemplo, el de la pestaña activa). */
    tipoInicial?: string;
};

/**
 * Campos del formulario de producto. La categoría y la subcategoría son
 * selects dependientes: al cambiar la categoría, la subcategoría se vacía y
 * solo ofrece las de esa categoría. Los usan los modales de crear y editar.
 */
export default function ProductoFields({
    producto,
    errors,
    medidas,
    categorias,
    tipoInicial,
}: ProductoFieldsProps) {
    const [categoriaId, setCategoriaId] = useState(
        String(producto?.categoria_id ?? ''),
    );
    const [subCategoriaId, setSubCategoriaId] = useState(
        String(producto?.sub_categoria_id ?? ''),
    );

    const subCategorias =
        categorias.find((categoria) => String(categoria.id) === categoriaId)
            ?.sub_categorias ?? [];

    function cambiarCategoria(nueva: string) {
        setCategoriaId(nueva);
        // La subcategoría elegida ya no corresponde a la nueva categoría.
        setSubCategoriaId('');
    }

    // Valor inicial de un campo numérico: vacío si el producto es nuevo o no lo tiene.
    const numero = (campo: keyof Producto): string | number => {
        const valor = producto?.[campo];

        return typeof valor === 'number' || typeof valor === 'string'
            ? valor
            : '';
    };

    return (
        <div className="space-y-4">
            <Seccion titulo="Identificación">
                {/* Lo primero que se elige: ocupa todo el ancho y se ven todas las opciones a la vez. */}
                <RadioGroupField
                    name="categoria_producto"
                    etiqueta="Tipo de producto"
                    defaultValue={
                        producto?.categoria_producto ??
                        tipoInicial ??
                        'PRODUCTO_TERMINADO'
                    }
                    opciones={CATEGORIAS_PRODUCTO}
                    columnas={3}
                    error={errors.categoria_producto}
                />
                <div className="grid gap-4 sm:grid-cols-2">
                    {/* El código lo genera el servidor según el tipo (MP-001, PT-001...) y no se edita. */}
                    <div className="grid gap-2">
                        <Label>Código</Label>
                        <div className="flex h-9 items-center rounded-md border bg-muted/40 px-3 font-mono text-sm text-muted-foreground">
                            {producto
                                ? producto.codigo_producto
                                : 'Se genera automáticamente'}
                        </div>
                    </div>
                    <TextField
                        name="nombre_producto"
                        etiqueta="Nombre"
                        defaultValue={producto?.nombre_producto}
                        required
                        error={errors.nombre_producto}
                    />
                </div>
                <TextField
                    name="descripcion_producto"
                    etiqueta="Descripción (opcional)"
                    defaultValue={producto?.descripcion_producto ?? ''}
                    error={errors.descripcion_producto}
                />
                <div className="grid gap-4 sm:grid-cols-2">
                    <TextField
                        name="marca"
                        etiqueta="Marca (opcional)"
                        defaultValue={producto?.marca ?? ''}
                        error={errors.marca}
                    />
                    <TextField
                        name="codigo_barra"
                        etiqueta="Código de barras (opcional)"
                        defaultValue={producto?.codigo_barra ?? ''}
                        error={errors.codigo_barra}
                    />
                </div>
            </Seccion>

            <Seccion titulo="Clasificación">
                <div className="grid gap-4 sm:grid-cols-2">
                    <SelectField
                        name="categoria_id"
                        etiqueta="Categoría"
                        value={categoriaId}
                        onValueChange={cambiarCategoria}
                        opciones={categorias.map((c) => ({
                            valor: c.id,
                            etiqueta: c.nombre_categoria,
                        }))}
                        placeholder="Selecciona una categoría"
                        error={errors.categoria_id}
                    />
                    <SelectField
                        name="sub_categoria_id"
                        etiqueta="Subcategoría"
                        value={subCategoriaId}
                        onValueChange={setSubCategoriaId}
                        opciones={subCategorias.map((s) => ({
                            valor: s.id,
                            etiqueta: s.nombre_sub_categoria,
                        }))}
                        placeholder={
                            categoriaId === ''
                                ? 'Primero elige una categoría'
                                : 'Selecciona una subcategoría'
                        }
                        disabled={categoriaId === ''}
                        error={errors.sub_categoria_id}
                    />
                    <SelectField
                        name="medida_id"
                        etiqueta="Medida"
                        defaultValue={producto?.medida_id ?? ''}
                        opciones={medidas.map((m) => ({
                            valor: m.id,
                            etiqueta: m.nombre,
                        }))}
                        placeholder="Selecciona una medida"
                        error={errors.medida_id}
                    />
                </div>
            </Seccion>

            <Seccion titulo="Precios">
                <div className="grid gap-4 sm:grid-cols-3">
                    <TextField
                        name="costo_producto"
                        etiqueta="Costo"
                        type="number"
                        min={0}
                        step="0.01"
                        defaultValue={numero('costo_producto')}
                        error={errors.costo_producto}
                    />
                    <TextField
                        name="valor_detal_producto"
                        etiqueta="Precio detal"
                        type="number"
                        min={0}
                        step="0.01"
                        defaultValue={numero('valor_detal_producto')}
                        error={errors.valor_detal_producto}
                    />
                    <TextField
                        name="valor_mayorista_producto"
                        etiqueta="Precio mayorista"
                        type="number"
                        min={0}
                        step="0.01"
                        defaultValue={numero('valor_mayorista_producto')}
                        error={errors.valor_mayorista_producto}
                    />
                    <TextField
                        name="valor_ferretero_producto"
                        etiqueta="Precio ferretero"
                        type="number"
                        min={0}
                        step="0.01"
                        defaultValue={numero('valor_ferretero_producto')}
                        error={errors.valor_ferretero_producto}
                    />
                    <TextField
                        name="valor_especial_producto"
                        etiqueta="Precio especial"
                        type="number"
                        min={0}
                        step="0.01"
                        defaultValue={numero('valor_especial_producto')}
                        error={errors.valor_especial_producto}
                    />
                    <TextField
                        name="iva_producto"
                        etiqueta="IVA (%)"
                        type="number"
                        min={0}
                        max={100}
                        step="0.01"
                        defaultValue={numero('iva_producto')}
                        error={errors.iva_producto}
                    />
                </div>
            </Seccion>

            <Seccion titulo="Inventario">
                <div className="grid gap-4 sm:grid-cols-2">
                    <TextField
                        name="alerta_producto"
                        etiqueta="Alerta de stock bajo"
                        type="number"
                        min={0}
                        defaultValue={numero('alerta_producto')}
                        error={errors.alerta_producto}
                    />
                    <TextField
                        name="ubicacion_producto"
                        etiqueta="Ubicación"
                        defaultValue={producto?.ubicacion_producto ?? ''}
                        error={errors.ubicacion_producto}
                    />
                </div>
                <div className="flex items-center gap-2">
                    <Checkbox
                        id="campo-activo"
                        name="activo"
                        defaultChecked={producto?.activo ?? true}
                    />
                    <Label htmlFor="campo-activo">Producto activo</Label>
                </div>
            </Seccion>

            <Seccion titulo="Imagen">
                <ImageField
                    id="imagen"
                    name="imagen"
                    nombreQuitar="quitar_imagen"
                    etiqueta="Foto del producto"
                    ayuda="PNG, JPG o WEBP. Máximo 2 MB."
                    urlActual={producto?.imagen_url ?? null}
                    error={errors.imagen}
                    aspectos={[
                        { etiqueta: 'Libre' },
                        { etiqueta: '1:1', valor: 1 },
                        { etiqueta: '4:3', valor: 4 / 3 },
                    ]}
                    maxLado={1024}
                />
            </Seccion>

            <details className="rounded-lg border p-4">
                <summary className="cursor-pointer text-sm font-medium">
                    Más detalles (opcional)
                </summary>
                <div className="mt-4 space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <TextField
                            name="tipo_producto"
                            etiqueta="Tipo"
                            defaultValue={producto?.tipo_producto ?? ''}
                            error={errors.tipo_producto}
                        />
                        <TextField
                            name="peso_producto"
                            etiqueta="Peso"
                            type="number"
                            min={0}
                            step="0.01"
                            defaultValue={numero('peso_producto')}
                            error={errors.peso_producto}
                        />
                        <SelectField
                            name="volumen_producto"
                            etiqueta="Volumen"
                            defaultValue={producto?.volumen_producto ?? ''}
                            opciones={VOLUMENES_PRODUCTO}
                            placeholder="Sin definir"
                            error={errors.volumen_producto}
                        />
                        <SelectField
                            name="tipo_compra"
                            etiqueta="Tipo de compra"
                            defaultValue={producto?.tipo_compra ?? ''}
                            opciones={TIPOS_COMPRA}
                            placeholder="Sin definir"
                            error={errors.tipo_compra}
                        />
                        <TextField
                            name="empaquetado_externo"
                            etiqueta="Empaque externo"
                            defaultValue={producto?.empaquetado_externo ?? ''}
                            error={errors.empaquetado_externo}
                        />
                        <TextField
                            name="empaquetado_interno"
                            etiqueta="Empaque interno"
                            defaultValue={producto?.empaquetado_interno ?? ''}
                            error={errors.empaquetado_interno}
                        />
                        <TextField
                            name="referencia_producto"
                            etiqueta="Referencia"
                            defaultValue={producto?.referencia_producto ?? ''}
                            error={errors.referencia_producto}
                        />
                        <TextField
                            name="codigo_cliente"
                            etiqueta="Código del cliente"
                            defaultValue={producto?.codigo_cliente ?? ''}
                            error={errors.codigo_cliente}
                        />
                    </div>
                    <TextField
                        name="codigo_importado"
                        etiqueta="Código de importación"
                        defaultValue={producto?.codigo_importado ?? ''}
                        error={errors.codigo_importado}
                    />
                    <div className="grid gap-2">
                        <Label htmlFor="campo-detalles_producto">
                            Detalles
                        </Label>
                        <textarea
                            id="campo-detalles_producto"
                            name="detalles_producto"
                            rows={3}
                            defaultValue={producto?.detalles_producto ?? ''}
                            className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                        <InputError message={errors.detalles_producto} />
                    </div>
                </div>
            </details>
        </div>
    );
}
