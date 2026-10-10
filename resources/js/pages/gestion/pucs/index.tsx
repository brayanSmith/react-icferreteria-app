import { Head } from '@inertiajs/react';
import PucController from '@/actions/App/Http/Controllers/Gestion/PucController';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import CrudTable from '@/components/crud/crud-table';
import ExportarMenu from '@/components/crud/exportar-menu';
import FormDialog from '@/components/crud/form-dialog';
import ImportacionesPanel from '@/components/crud/importaciones-panel';
import ImportarDialog from '@/components/crud/importar-dialog';
import PageHeader from '@/components/crud/page-header';
import SelectField from '@/components/crud/select-field';
import TextField from '@/components/crud/text-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { CLASES_PUC, TIPOS_PUC } from '@/lib/catalogos';
import { index } from '@/routes/gestion/pucs';
import type {
    Importacion,
    ImportacionPendiente,
    Puc,
} from '@/types';

/**
 * Campos del formulario de cuenta; los usan los modales de crear y editar.
 */
function CamposPuc({
    puc,
    errors,
}: {
    puc?: Puc;
    errors: Record<string, string>;
}) {
    return (
        <>
            <SelectField
                name="tipo"
                etiqueta="Clase de cuenta"
                defaultValue={puc?.tipo ?? '1'}
                opciones={TIPOS_PUC}
                error={errors.tipo}
            />
            <div className="grid gap-4 sm:grid-cols-2">
                <TextField
                    name="cuenta"
                    etiqueta="Cuenta"
                    defaultValue={puc?.cuenta}
                    placeholder="1105"
                    required
                    error={errors.cuenta}
                />
                <TextField
                    name="subcuenta"
                    etiqueta="Subcuenta"
                    defaultValue={puc?.subcuenta}
                    placeholder="110505"
                    required
                    error={errors.subcuenta}
                />
            </div>
            <TextField
                name="concepto"
                etiqueta="Concepto"
                defaultValue={puc?.concepto}
                placeholder="Caja general"
                required
                error={errors.concepto}
            />
            <TextField
                name="descripcion"
                etiqueta="Descripción (opcional)"
                defaultValue={puc?.descripcion ?? ''}
                error={errors.descripcion}
            />
        </>
    );
}

type Props = {
    cuentas: { data: Puc[] };
    importaciones: { data: Importacion[] };
    importacionPendiente: ImportacionPendiente | null;
};

/**
 * Plan único de cuentas (PUC) del tenant, con búsqueda y paginación.
 */
export default function PucsIndex({
    cuentas,
    importaciones,
    importacionPendiente,
}: Props) {
    const can = useCan();

    return (
        <>
            <Head title="Plan de cuentas" />

            <div className="space-y-6 p-4">
                <PageHeader
                    titulo="Plan de cuentas (PUC)"
                    descripcion="Cuentas contables que usa tu empresa"
                    accion={
                        <div className="flex flex-wrap gap-2">
                            <ExportarMenu modulo="pucs" />
                            {can('pucs.crear') && (
                                <>
                                    <ImportarDialog
                                        tipos={[
                                            {
                                                valor: 'pucs',
                                                etiqueta: 'Plan de cuentas',
                                            },
                                        ]}
                                        pendiente={importacionPendiente}
                                    />
                                    <FormDialog
                                        titulo="Nueva cuenta"
                                        accion={PucController.store.form()}
                                        etiquetaGuardar="Crear cuenta"
                                        trigger={<Button>Nueva cuenta</Button>}
                                    >
                                        {({ errors }) => (
                                            <CamposPuc errors={errors} />
                                        )}
                                    </FormDialog>
                                </>
                            )}
                        </div>
                    }
                />

                <ImportacionesPanel importaciones={importaciones.data} />

                <CrudTable
                    filas={cuentas.data}
                    busqueda={{
                        placeholder: 'Buscar por cuenta, subcuenta o concepto',
                        texto: (c) =>
                            [c.cuenta, c.subcuenta, c.concepto].join(' '),
                    }}
                    clave={(cuenta) => cuenta.id}
                    vacio="No hay cuentas que coincidan."
                    columnas={[
                        {
                            encabezado: 'Clase',
                            filtro: 'lista',
                            valorFiltro: (c) => CLASES_PUC[c.tipo] ?? c.tipo,
                            celda: (c) => (
                                <Badge variant="secondary">
                                    {CLASES_PUC[c.tipo] ?? c.tipo}
                                </Badge>
                            ),
                        },
                        {
                            encabezado: 'Cuenta',
                            celda: (c) => c.cuenta,
                            valorOrden: (c) => c.cuenta,
                        },
                        {
                            encabezado: 'Subcuenta',
                            valorOrden: (c) => c.subcuenta,
                            celda: (c) => (
                                <span className="font-mono">{c.subcuenta}</span>
                            ),
                        },
                        {
                            encabezado: 'Concepto',
                            valorFiltro: (c) => c.concepto,
                            celda: (c) => (
                                <span className="font-medium">
                                    {c.concepto}
                                </span>
                            ),
                        },
                        {
                            encabezado: 'Descripción',
                            valorFiltro: (c) => c.descripcion ?? '',
                            celda: (c) => c.descripcion ?? '—',
                        },
                    ]}
                    acciones={(cuenta) => (
                        <>
                            {can('pucs.editar') && (
                                <FormDialog
                                    titulo={`Editar ${cuenta.subcuenta}`}
                                    accion={PucController.update.form(
                                        cuenta.id,
                                    )}
                                    etiquetaGuardar="Guardar cambios"
                                    trigger={
                                        <Button size="sm" variant="outline">
                                            Editar
                                        </Button>
                                    }
                                >
                                    {({ errors }) => (
                                        <CamposPuc
                                            puc={cuenta}
                                            errors={errors}
                                        />
                                    )}
                                </FormDialog>
                            )}
                            {can('pucs.eliminar') && (
                                <ConfirmDeleteDialog
                                    titulo={`¿Eliminar la cuenta ${cuenta.subcuenta}?`}
                                    descripcion="La cuenta dejará de estar disponible."
                                    accion={PucController.destroy.form(
                                        cuenta.id,
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

PucsIndex.layout = {
    breadcrumbs: [{ title: 'Plan de cuentas', href: index() }],
};
