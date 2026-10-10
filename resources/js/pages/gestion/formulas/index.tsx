import { Head, Link } from '@inertiajs/react';
import FormulaController from '@/actions/App/Http/Controllers/Gestion/FormulaController';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import CrudTable from '@/components/crud/crud-table';
import FormDialog from '@/components/crud/form-dialog';
import PageHeader from '@/components/crud/page-header';
import FormulaFields from '@/components/formulas/formula-fields';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { index } from '@/routes/gestion/formulas';
import { index as productosIndex } from '@/routes/gestion/productos';
import type { Formula, ProductoIngrediente } from '@/types';

type Props = {
    formulas: { data: Formula[] };
    productos: ProductoIngrediente[];
};

const numero = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 });

/** Cuántos ingredientes se listan en la tabla antes de resumir el resto. */
const INGREDIENTES_VISIBLES = 3;

/**
 * Fórmulas (recetas) del tenant. Cada una se crea y se edita junto con su
 * detalle de ingredientes en un solo modal y un solo guardado.
 */
export default function FormulasIndex({ formulas, productos }: Props) {
    const can = useCan();

    return (
        <>
            <Head title="Fórmulas" />

            <div className="space-y-6 p-4">
                <PageHeader
                    titulo="Fórmulas"
                    descripcion="Recetas de producción con sus ingredientes"
                    accion={
                        can('formulas.crear') &&
                        productos.length > 0 && (
                            <FormDialog
                                titulo="Nueva fórmula"
                                descripcion="La fórmula se guarda junto con todos sus ingredientes."
                                accion={FormulaController.store.form()}
                                etiquetaGuardar="Guardar fórmula"
                                ancho="xl"
                                trigger={<Button>Nueva fórmula</Button>}
                            >
                                {({ errors }) => (
                                    <FormulaFields
                                        errors={errors}
                                        productos={productos}
                                    />
                                )}
                            </FormDialog>
                        )
                    }
                />

                {productos.length === 0 && (
                    <p className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                        Para crear una fórmula necesitas tener al menos un{' '}
                        <Link
                            href={productosIndex()}
                            className="font-medium underline underline-offset-4"
                        >
                            producto de tipo Materia prima
                        </Link>{' '}
                        registrado.
                    </p>
                )}

                <CrudTable
                    filas={formulas.data}
                    busqueda={{
                        placeholder: 'Buscar por nombre de la fórmula',
                        texto: (f) => f.nombre_formula,
                    }}
                    clave={(formula) => formula.id}
                    vacio="No hay fórmulas que coincidan."
                    columnas={[
                        {
                            encabezado: 'Fórmula',
                            valorOrden: (f) => f.nombre_formula,
                            celda: (f) => (
                                <div>
                                    <p className="font-medium">
                                        {f.nombre_formula}
                                    </p>
                                    {f.descripcion_formula && (
                                        <p className="max-w-xs truncate text-muted-foreground">
                                            {f.descripcion_formula}
                                        </p>
                                    )}
                                </div>
                            ),
                        },
                        {
                            encabezado: 'Ingredientes',
                            valorFiltro: (f) => f.detalles.map((d) => d.producto).join(' '),
                            celda: (f) => (
                                <div className="space-y-1">
                                    <Badge variant="secondary">
                                        {f.detalles.length} ingredientes
                                    </Badge>
                                    <ul className="text-muted-foreground">
                                        {f.detalles
                                            .slice(0, INGREDIENTES_VISIBLES)
                                            .map((detalle) => (
                                                <li key={detalle.producto_id}>
                                                    {detalle.producto} ·{' '}
                                                    {numero.format(
                                                        detalle.cantidad_producto,
                                                    )}
                                                    {detalle.medida
                                                        ? ` ${detalle.medida}`
                                                        : ''}
                                                </li>
                                            ))}
                                        {f.detalles.length >
                                            INGREDIENTES_VISIBLES && (
                                            <li>
                                                y{' '}
                                                {f.detalles.length -
                                                    INGREDIENTES_VISIBLES}{' '}
                                                más…
                                            </li>
                                        )}
                                    </ul>
                                </div>
                            ),
                        },
                    ]}
                    acciones={(formula) => (
                        <>
                            {can('formulas.editar') && (
                                <FormDialog
                                    titulo={`Editar ${formula.nombre_formula}`}
                                    descripcion="Los cambios se guardan junto con todos los ingredientes."
                                    accion={FormulaController.update.form(
                                        formula.id,
                                    )}
                                    etiquetaGuardar="Guardar cambios"
                                    ancho="xl"
                                    trigger={
                                        <Button size="sm" variant="outline">
                                            Editar
                                        </Button>
                                    }
                                >
                                    {({ errors }) => (
                                        <FormulaFields
                                            formula={formula}
                                            errors={errors}
                                            productos={productos}
                                        />
                                    )}
                                </FormDialog>
                            )}
                            {can('formulas.eliminar') && (
                                <ConfirmDeleteDialog
                                    titulo={`¿Eliminar la fórmula ${formula.nombre_formula}?`}
                                    descripcion="Se elimina también su detalle. No se puede si ya tiene producciones registradas."
                                    accion={FormulaController.destroy.form(
                                        formula.id,
                                    )}
                                />
                            )}
                        </>
                    )}
                />
            </div>
        </>
    );
}

FormulasIndex.layout = {
    breadcrumbs: [{ title: 'Fórmulas', href: index() }],
};
