import { X } from 'lucide-react';
import { useState } from 'react';
import SelectField from '@/components/crud/select-field';
import TextField from '@/components/crud/text-field';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { Formula, ProductoIngrediente } from '@/types';

/** Una fila del detalle mientras se edita; la clave estable evita mezclar valores al quitar filas. */
type FilaIngrediente = {
    clave: number;
    productoId: string;
    cantidad: string;
};

type FormulaFieldsProps = {
    formula?: Formula;
    errors: Record<string, string>;
    productos: ProductoIngrediente[];
};

/**
 * Campos del formulario de fórmula: nombre, descripción y el detalle de
 * ingredientes en la misma pantalla. Todo viaja en un solo envío: los
 * ingredientes salen como `detalles[i][producto_id]` y
 * `detalles[i][cantidad_producto]`, y el servidor los guarda junto con la fórmula.
 *
 * El detalle se edita en una tabla con aspecto de hoja de cálculo: las celdas
 * no muestran cajas, solo se resaltan al pasar el ratón o al tener el foco.
 */
export default function FormulaFields({
    formula,
    errors,
    productos,
}: FormulaFieldsProps) {
    const inicial: FilaIngrediente[] = formula?.detalles.length
        ? formula.detalles.map((detalle, posicion) => ({
              clave: posicion,
              productoId: String(detalle.producto_id),
              cantidad: String(detalle.cantidad_producto),
          }))
        : [{ clave: 0, productoId: '', cantidad: '' }];

    const [filas, setFilas] = useState<FilaIngrediente[]>(inicial);
    const [siguienteClave, setSiguienteClave] = useState(inicial.length);

    function agregar() {
        setFilas((actuales) => [
            ...actuales,
            { clave: siguienteClave, productoId: '', cantidad: '' },
        ]);
        setSiguienteClave((actual) => actual + 1);
    }

    function quitar(clave: number) {
        setFilas((actuales) => actuales.filter((fila) => fila.clave !== clave));
    }

    function cambiarProducto(clave: number, productoId: string) {
        setFilas((actuales) =>
            actuales.map((fila) =>
                fila.clave === clave ? { ...fila, productoId } : fila,
            ),
        );
    }

    // Solo la materia prima puede ser ingrediente. Una fórmula antigua puede traer
    // un producto que ya no lo es: se muestra marcado para que se vea qué reemplazar
    // (al guardar, el servidor pide cambiarlo).
    const antiguos: ProductoIngrediente[] = (formula?.detalles ?? [])
        .filter(
            (detalle) => !productos.some((p) => p.id === detalle.producto_id),
        )
        .map((detalle) => ({
            id: detalle.producto_id,
            nombre: `${detalle.producto} (no es materia prima)`,
            medida: detalle.medida,
        }));
    const disponibles = [...productos, ...antiguos];

    // Productos ya usados en otras filas: no se ofrecen de nuevo, porque no pueden repetirse.
    const usados = new Set(
        filas.map((fila) => fila.productoId).filter((id) => id !== ''),
    );

    return (
        <div className="space-y-4">
            <TextField
                name="nombre_formula"
                etiqueta="Nombre de la fórmula"
                defaultValue={formula?.nombre_formula}
                placeholder="Pintura blanca base"
                required
                error={errors.nombre_formula}
            />

            <div className="grid gap-2">
                <Label htmlFor="campo-descripcion_formula">
                    Descripción (opcional)
                </Label>
                <textarea
                    id="campo-descripcion_formula"
                    name="descripcion_formula"
                    rows={2}
                    defaultValue={formula?.descripcion_formula ?? ''}
                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <InputError message={errors.descripcion_formula} />
            </div>

            <section className="space-y-2">
                <div className="flex items-end justify-between gap-3">
                    <div>
                        <h3 className="text-sm font-medium">Ingredientes</h3>
                        <p className="text-sm text-muted-foreground">
                            Solo se pueden usar productos de tipo Materia prima.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={agregar}
                    >
                        Agregar ingrediente
                    </Button>
                </div>

                <InputError message={errors.detalles} />

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr className="divide-x">
                                <th className="px-3 py-2 font-medium">
                                    Producto
                                </th>
                                <th className="w-32 px-3 py-2 font-medium">
                                    Medida
                                </th>
                                <th className="w-36 px-3 py-2 text-right font-medium">
                                    Cantidad
                                </th>
                                <th className="w-10">
                                    <span className="sr-only">Quitar</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {filas.map((fila, posicion) => {
                                const producto = disponibles.find(
                                    (p) => String(p.id) === fila.productoId,
                                );

                                return (
                                    <tr
                                        key={fila.clave}
                                        className="divide-x align-top"
                                    >
                                        <td className="p-0 [&_p]:px-3 [&_p]:pb-2">
                                            <SelectField
                                                plano
                                                name={`detalles[${posicion}][producto_id]`}
                                                etiqueta={`Producto del ingrediente ${posicion + 1}`}
                                                etiquetaOculta
                                                value={fila.productoId}
                                                onValueChange={(valor) =>
                                                    cambiarProducto(
                                                        fila.clave,
                                                        valor,
                                                    )
                                                }
                                                opciones={disponibles
                                                    .filter(
                                                        (p) =>
                                                            String(p.id) ===
                                                                fila.productoId ||
                                                            !usados.has(
                                                                String(p.id),
                                                            ),
                                                    )
                                                    .map((p) => ({
                                                        valor: p.id,
                                                        etiqueta: p.nombre,
                                                    }))}
                                                placeholder="Selecciona un producto"
                                                error={
                                                    errors[
                                                        `detalles.${posicion}.producto_id`
                                                    ]
                                                }
                                            />
                                        </td>
                                        <td className="px-3 py-2 text-muted-foreground">
                                            {producto?.medida ?? '—'}
                                        </td>
                                        <td className="p-0 [&_p]:px-3 [&_p]:pb-2">
                                            <TextField
                                                plano
                                                inputClassName="text-right"
                                                name={`detalles[${posicion}][cantidad_producto]`}
                                                etiqueta={`Cantidad del ingrediente ${posicion + 1}`}
                                                etiquetaOculta
                                                type="number"
                                                min={0}
                                                step="0.01"
                                                defaultValue={fila.cantidad}
                                                placeholder="0"
                                                error={
                                                    errors[
                                                        `detalles.${posicion}.cantidad_producto`
                                                    ]
                                                }
                                            />
                                        </td>
                                        <td className="p-0 text-center">
                                            {filas.length > 1 && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-9 rounded-none text-muted-foreground hover:text-destructive"
                                                    aria-label={`Quitar el ingrediente ${posicion + 1}`}
                                                    onClick={() =>
                                                        quitar(fila.clave)
                                                    }
                                                >
                                                    <X />
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    );
}
