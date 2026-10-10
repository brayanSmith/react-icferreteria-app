import RadioGroupField from '@/components/crud/radio-group-field';
import SelectField from '@/components/crud/select-field';
import TextField from '@/components/crud/text-field';
import { TIPOS_MOVIMIENTO_STOCK } from '@/lib/catalogos';
import type { MovimientoStock, OpcionSelect } from '@/types';

type CamposMovimientoProps = {
    /** Movimiento que se corrige; sin él, el formulario es de un movimiento nuevo. */
    movimiento?: MovimientoStock;
    errors: Record<string, string>;
    /** Solo en un movimiento nuevo: qué producto y en qué bodega se mueve. */
    productos?: OpcionSelect[];
    bodegas?: OpcionSelect[];
    /** Producto y bodega ya conocidos (desde el detalle de un stock): viajan ocultos. */
    fijo?: { productoId: number; bodegaId: number };
};

/**
 * Campos de un movimiento de inventario. Al crear desde el listado se elige
 * producto y bodega; desde el detalle de un stock ya se conocen. Al corregir
 * solo cambian el tipo, la cantidad y la descripción.
 */
export default function CamposMovimiento({
    movimiento,
    errors,
    productos = [],
    bodegas = [],
    fijo,
}: CamposMovimientoProps) {
    return (
        <>
            {fijo && (
                <>
                    <input
                        type="hidden"
                        name="producto_id"
                        value={fijo.productoId}
                    />
                    <input
                        type="hidden"
                        name="bodega_id"
                        value={fijo.bodegaId}
                    />
                </>
            )}

            {!movimiento && !fijo && (
                <>
                    <SelectField
                        name="producto_id"
                        etiqueta="Producto"
                        opciones={productos.map((p) => ({
                            valor: p.id,
                            etiqueta: p.nombre,
                        }))}
                        placeholder="Selecciona un producto"
                        error={errors.producto_id}
                    />
                    <SelectField
                        name="bodega_id"
                        etiqueta="Bodega"
                        opciones={bodegas.map((b) => ({
                            valor: b.id,
                            etiqueta: b.nombre,
                        }))}
                        placeholder="Selecciona una bodega"
                        error={errors.bodega_id}
                    />
                </>
            )}

            <RadioGroupField
                name="tipo"
                etiqueta="Tipo de movimiento"
                opciones={TIPOS_MOVIMIENTO_STOCK}
                defaultValue={movimiento?.tipo ?? 'INGRESO'}
                error={errors.tipo}
            />
            <TextField
                name="cantidad"
                etiqueta="Cantidad"
                type="number"
                min={0}
                step="0.01"
                defaultValue={movimiento?.cantidad ?? ''}
                required
                error={errors.cantidad}
            />
            <TextField
                name="descripcion"
                etiqueta="Motivo"
                placeholder="Compra, ajuste de inventario, merma..."
                defaultValue={movimiento?.descripcion ?? ''}
                required
                error={errors.descripcion}
            />
        </>
    );
}
