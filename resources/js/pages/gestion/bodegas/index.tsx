import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import BodegaController from '@/actions/App/Http/Controllers/Gestion/BodegaController';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import CrudTable from '@/components/crud/crud-table';
import FormDialog from '@/components/crud/form-dialog';
import EliminadosToggle from '@/components/crud/eliminados-toggle';
import PageHeader from '@/components/crud/page-header';
import TextField from '@/components/crud/text-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { index, restore } from '@/routes/gestion/bodegas';
import type { Bodega } from '@/types';

/**
 * Campos del formulario de bodega; los usan los modales de crear y editar.
 */
function CamposBodega({
    bodega,
    errors,
}: {
    bodega?: Bodega;
    errors: Record<string, string>;
}) {
    return (
        <>
            <TextField
                name="nombre_bodega"
                etiqueta="Nombre de la bodega"
                defaultValue={bodega?.nombre_bodega}
                placeholder="Bodega principal"
                required
                error={errors.nombre_bodega}
            />
            <TextField
                name="ubicacion_bodega"
                etiqueta="Ubicación (opcional)"
                defaultValue={bodega?.ubicacion_bodega ?? ''}
                error={errors.ubicacion_bodega}
            />
        </>
    );
}

type Props = {
    bodegas: { data: Bodega[] };
};

/**
 * Bodegas del tenant. Se eliminan de forma lógica y se restauran desde la
 * vista "Ver eliminadas".
 */
export default function BodegasIndex({ bodegas }: Props) {
    const can = useCan();
    const [verEliminadas, setVerEliminadas] = useState(false);
    const filas = useMemo(
        () =>
            bodegas.data.filter(
                (bodega) => (bodega.deleted_at !== null) === verEliminadas,
            ),
        [bodegas.data, verEliminadas],
    );

    return (
        <>
            <Head title="Bodegas" />

            <div className="space-y-6 p-4">
                <PageHeader
                    titulo="Bodegas"
                    descripcion="Lugares donde guardas tu inventario"
                    accion={
                        can('bodegas.crear') && (
                            <FormDialog
                                titulo="Nueva bodega"
                                accion={BodegaController.store.form()}
                                etiquetaGuardar="Crear bodega"
                                trigger={<Button>Nueva bodega</Button>}
                            >
                                {({ errors }) => (
                                    <CamposBodega errors={errors} />
                                )}
                            </FormDialog>
                        )
                    }
                />

                <CrudTable
                    filas={filas}
                    barra={
                        <EliminadosToggle
                            id="ver-eliminadas"
                            etiqueta="Ver eliminadas"
                            activo={verEliminadas}
                            onCambiar={setVerEliminadas}
                        />
                    }
                    clave={(bodega) => bodega.id}
                    vacio={
                        verEliminadas
                            ? 'No hay bodegas eliminadas.'
                            : 'Todavía no hay bodegas.'
                    }
                    columnas={[
                        {
                            encabezado: 'Bodega',
                            valorOrden: (b) => b.nombre_bodega,
                            celda: (b) => (
                                <span className="font-medium">
                                    {b.nombre_bodega}
                                </span>
                            ),
                        },
                        {
                            encabezado: 'Ubicación',
                            valorFiltro: (b) => b.ubicacion_bodega ?? '',
                            celda: (b) => b.ubicacion_bodega ?? '—',
                        },
                        {
                            encabezado: 'Productos',
                            alinear: 'derecha',
                            valorOrden: (b) => b.productos_count ?? 0,
                            celda: (b) => b.productos_count ?? 0,
                        },
                        {
                            encabezado: 'Estado',
                            filtro: 'lista',
                            valorFiltro: (b) => (b.deleted_at ? 'Eliminada' : 'Activa'),
                            celda: (b) =>
                                b.deleted_at ? (
                                    <Badge variant="destructive">
                                        Eliminada
                                    </Badge>
                                ) : (
                                    <Badge>Activa</Badge>
                                ),
                        },
                    ]}
                    acciones={(bodega) =>
                        bodega.deleted_at ? (
                            can('bodegas.eliminar') && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={restore(bodega.id)}
                                        as="button"
                                        preserveScroll
                                    >
                                        Restaurar
                                    </Link>
                                </Button>
                            )
                        ) : (
                            <>
                                {can('bodegas.editar') && (
                                    <FormDialog
                                        titulo={`Editar ${bodega.nombre_bodega}`}
                                        accion={BodegaController.update.form(
                                            bodega.id,
                                        )}
                                        etiquetaGuardar="Guardar cambios"
                                        trigger={
                                            <Button size="sm" variant="outline">
                                                Editar
                                            </Button>
                                        }
                                    >
                                        {({ errors }) => (
                                            <CamposBodega
                                                bodega={bodega}
                                                errors={errors}
                                            />
                                        )}
                                    </FormDialog>
                                )}
                                {can('bodegas.eliminar') && (
                                    <ConfirmDeleteDialog
                                        titulo={`¿Eliminar la bodega ${bodega.nombre_bodega}?`}
                                        descripcion="Solo se puede eliminar si no tiene productos ni movimientos. Podrás restaurarla desde 'Ver eliminadas'."
                                        accion={BodegaController.destroy.form(
                                            bodega.id,
                                        )}
                                    />
                                )}
                            </>
                        )
                    }
                />
            </div>
        </>
    );
}

BodegasIndex.layout = {
    breadcrumbs: [{ title: 'Bodegas', href: index() }],
};
