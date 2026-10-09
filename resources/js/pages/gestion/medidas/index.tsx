import { Head } from '@inertiajs/react';
import MedidaController from '@/actions/App/Http/Controllers/Gestion/MedidaController';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import CrudTable from '@/components/crud/crud-table';
import FormDialog from '@/components/crud/form-dialog';
import PageHeader from '@/components/crud/page-header';
import RadioGroupField from '@/components/crud/radio-group-field';
import TextField from '@/components/crud/text-field';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { TIPOS_MEDIDA, etiquetaDe } from '@/lib/catalogos';
import { index } from '@/routes/gestion/medidas';
import type { Medida } from '@/types';

/**
 * Campos del formulario de medida; los usan los modales de crear y editar.
 */
function CamposMedida({
    medida,
    errors,
}: {
    medida?: Medida;
    errors: Record<string, string>;
}) {
    return (
        <>
            <TextField
                name="nombre_medida"
                etiqueta="Nombre"
                defaultValue={medida?.nombre_medida}
                placeholder="Kilogramo"
                required
                error={errors.nombre_medida}
            />
            <RadioGroupField
                name="tipo_medida"
                etiqueta="Tipo"
                defaultValue={medida?.tipo_medida ?? 'CANTIDAD'}
                opciones={TIPOS_MEDIDA}
                error={errors.tipo_medida}
            />
            <TextField
                name="descripcion_medida"
                etiqueta="Descripción (opcional)"
                defaultValue={medida?.descripcion_medida ?? ''}
                error={errors.descripcion_medida}
            />
        </>
    );
}

/**
 * Catálogo de unidades de medida del tenant.
 */
export default function MedidasIndex({
    medidas,
}: {
    medidas: { data: Medida[] };
}) {
    const can = useCan();

    return (
        <>
            <Head title="Medidas" />

            <div className="space-y-6 p-4">
                <PageHeader
                    titulo="Medidas"
                    descripcion="Unidades con las que se miden tus productos"
                    accion={
                        can('medidas.crear') && (
                            <FormDialog
                                titulo="Nueva medida"
                                accion={MedidaController.store.form()}
                                etiquetaGuardar="Crear medida"
                                trigger={<Button>Nueva medida</Button>}
                            >
                                {({ errors }) => (
                                    <CamposMedida errors={errors} />
                                )}
                            </FormDialog>
                        )
                    }
                />

                <CrudTable
                    filas={medidas.data}
                    clave={(medida) => medida.id}
                    vacio="Todavía no hay medidas. Crea la primera para poder registrar productos."
                    columnas={[
                        {
                            encabezado: 'Nombre',
                            valorOrden: (m) => m.nombre_medida,
                            celda: (m) => (
                                <span className="font-medium">
                                    {m.nombre_medida}
                                </span>
                            ),
                        },
                        {
                            encabezado: 'Tipo',
                            valorOrden: (m) => m.tipo_medida,
                            celda: (m) =>
                                etiquetaDe(TIPOS_MEDIDA, m.tipo_medida),
                        },
                        {
                            encabezado: 'Descripción',
                            celda: (m) => m.descripcion_medida ?? '—',
                        },
                        {
                            encabezado: 'Productos',
                            alinear: 'derecha',
                            valorOrden: (m) => m.productos_count ?? 0,
                            celda: (m) => m.productos_count ?? 0,
                        },
                    ]}
                    acciones={(medida) => (
                        <>
                            {can('medidas.editar') && (
                                <FormDialog
                                    titulo={`Editar ${medida.nombre_medida}`}
                                    accion={MedidaController.update.form(
                                        medida.id,
                                    )}
                                    etiquetaGuardar="Guardar cambios"
                                    trigger={
                                        <Button size="sm" variant="outline">
                                            Editar
                                        </Button>
                                    }
                                >
                                    {({ errors }) => (
                                        <CamposMedida
                                            medida={medida}
                                            errors={errors}
                                        />
                                    )}
                                </FormDialog>
                            )}
                            {can('medidas.eliminar') && (
                                <ConfirmDeleteDialog
                                    titulo={`¿Eliminar la medida ${medida.nombre_medida}?`}
                                    descripcion="Solo se puede eliminar si ningún producto la usa."
                                    accion={MedidaController.destroy.form(
                                        medida.id,
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

MedidasIndex.layout = {
    breadcrumbs: [{ title: 'Medidas', href: index() }],
};
