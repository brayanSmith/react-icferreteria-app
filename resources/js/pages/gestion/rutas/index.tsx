import { Head } from '@inertiajs/react';
import RutaController from '@/actions/App/Http/Controllers/Gestion/RutaController';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import CrudTable from '@/components/crud/crud-table';
import FormDialog from '@/components/crud/form-dialog';
import PageHeader from '@/components/crud/page-header';
import TextField from '@/components/crud/text-field';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { index } from '@/routes/gestion/rutas';
import type { Ruta } from '@/types';

/**
 * Campos del formulario de ruta; los usan los modales de crear y editar.
 */
function CamposRuta({
    ruta,
    errors,
}: {
    ruta?: Ruta;
    errors: Record<string, string>;
}) {
    return (
        <>
            <TextField
                name="ruta"
                etiqueta="Nombre de la ruta"
                defaultValue={ruta?.ruta}
                placeholder="Zona norte"
                required
                error={errors.ruta}
            />
            <TextField
                name="descripcion"
                etiqueta="Descripción (opcional)"
                defaultValue={ruta?.descripcion ?? ''}
                error={errors.descripcion}
            />
        </>
    );
}

/**
 * Rutas de reparto a las que se asignan los clientes.
 */
export default function RutasIndex({ rutas }: { rutas: { data: Ruta[] } }) {
    const can = useCan();

    return (
        <>
            <Head title="Rutas" />

            <div className="space-y-6 p-4">
                <PageHeader
                    titulo="Rutas"
                    descripcion="Rutas de reparto de tus clientes"
                    accion={
                        can('rutas.crear') && (
                            <FormDialog
                                titulo="Nueva ruta"
                                accion={RutaController.store.form()}
                                etiquetaGuardar="Crear ruta"
                                trigger={<Button>Nueva ruta</Button>}
                            >
                                {({ errors }) => <CamposRuta errors={errors} />}
                            </FormDialog>
                        )
                    }
                />

                <CrudTable
                    filas={rutas.data}
                    clave={(ruta) => ruta.id}
                    vacio="Todavía no hay rutas."
                    columnas={[
                        {
                            encabezado: 'Ruta',
                            valorOrden: (r) => r.ruta,
                            celda: (r) => (
                                <span className="font-medium">{r.ruta}</span>
                            ),
                        },
                        {
                            encabezado: 'Descripción',
                            celda: (r) => r.descripcion ?? '—',
                        },
                        {
                            encabezado: 'Clientes',
                            alinear: 'derecha',
                            valorOrden: (r) => r.clientes_count ?? 0,
                            celda: (r) => r.clientes_count ?? 0,
                        },
                    ]}
                    acciones={(ruta) => (
                        <>
                            {can('rutas.editar') && (
                                <FormDialog
                                    titulo={`Editar ${ruta.ruta}`}
                                    accion={RutaController.update.form(ruta.id)}
                                    etiquetaGuardar="Guardar cambios"
                                    trigger={
                                        <Button size="sm" variant="outline">
                                            Editar
                                        </Button>
                                    }
                                >
                                    {({ errors }) => (
                                        <CamposRuta
                                            ruta={ruta}
                                            errors={errors}
                                        />
                                    )}
                                </FormDialog>
                            )}
                            {can('rutas.eliminar') && (
                                <ConfirmDeleteDialog
                                    titulo={`¿Eliminar la ruta ${ruta.ruta}?`}
                                    descripcion="Solo se puede eliminar si no tiene clientes asignados."
                                    accion={RutaController.destroy.form(
                                        ruta.id,
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

RutasIndex.layout = {
    breadcrumbs: [{ title: 'Rutas', href: index() }],
};
