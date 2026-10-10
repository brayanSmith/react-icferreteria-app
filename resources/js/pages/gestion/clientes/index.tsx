import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import ClienteController from '@/actions/App/Http/Controllers/Gestion/ClienteController';
import ClienteFields from '@/components/clientes/cliente-fields';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import CrudTable from '@/components/crud/crud-table';
import ExportarMenu from '@/components/crud/exportar-menu';
import FormDialog from '@/components/crud/form-dialog';
import ImportacionesPanel from '@/components/crud/importaciones-panel';
import ImportarDialog from '@/components/crud/importar-dialog';
import EliminadosToggle from '@/components/crud/eliminados-toggle';
import PageHeader from '@/components/crud/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { TIPOS_CLIENTE, etiquetaDe } from '@/lib/catalogos';
import { index, restore } from '@/routes/gestion/clientes';
import type {
    Cliente,
    Importacion,
    ImportacionPendiente,
    OpcionSelect,
} from '@/types';

type Props = {
    clientes: { data: Cliente[] };
    rutas: OpcionSelect[];
    comerciales: OpcionSelect[];
    importaciones: { data: Importacion[] };
    importacionPendiente: ImportacionPendiente | null;
};

const moneda = new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
});

/**
 * Clientes del tenant, con búsqueda y paginación en el navegador. Se eliminan de forma lógica y
 * se restauran desde "Ver eliminados".
 */
export default function ClientesIndex({
    clientes,
    rutas,
    comerciales,
    importaciones,
    importacionPendiente,
}: Props) {
    const can = useCan();
    const [verEliminados, setVerEliminados] = useState(false);
    const filas = useMemo(
        () =>
            clientes.data.filter(
                (cliente) => (cliente.deleted_at !== null) === verEliminados,
            ),
        [clientes.data, verEliminados],
    );

    return (
        <>
            <Head title="Clientes" />

            <div className="space-y-6 p-4">
                <PageHeader
                    titulo="Clientes"
                    descripcion="Personas y empresas a las que les vendes"
                    accion={
                        <div className="flex flex-wrap gap-2">
                            <ExportarMenu modulo="clientes" />
                            {can('clientes.crear') && (
                                <>
                                    <ImportarDialog
                                        tipos={[
                                            {
                                                valor: 'clientes',
                                                etiqueta: 'Clientes',
                                            },
                                        ]}
                                        pendiente={importacionPendiente}
                                    />
                                    <FormDialog
                                        titulo="Nuevo cliente"
                                        accion={ClienteController.store.form()}
                                        etiquetaGuardar="Crear cliente"
                                        ancho="xl"
                                        trigger={<Button>Nuevo cliente</Button>}
                                    >
                                        {({ errors }) => (
                                            <ClienteFields
                                                errors={errors}
                                                rutas={rutas}
                                                comerciales={comerciales}
                                            />
                                        )}
                                    </FormDialog>
                                </>
                            )}
                        </div>
                    }
                />

                <ImportacionesPanel importaciones={importaciones.data} />

                <CrudTable
                    filas={filas}
                    busqueda={{
                        placeholder:
                            'Buscar por nombre, documento, correo o teléfono',
                        texto: (c) =>
                            [c.razon_social, c.numero_documento, c.email, c.telefono].join(' '),
                    }}
                    barra={
                        <EliminadosToggle
                            id="ver-eliminados"
                            etiqueta="Ver eliminados"
                            activo={verEliminados}
                            onCambiar={setVerEliminados}
                        />
                    }
                    clave={(cliente) => cliente.id}
                    vacio={
                        verEliminados
                            ? 'No hay clientes eliminados.'
                            : 'No hay clientes que coincidan.'
                    }
                    columnas={[
                        {
                            encabezado: 'N°',
                            celda: (c) => c.numero,
                            valorOrden: (c) => c.numero,
                        },
                        {
                            encabezado: 'Cliente',
                            valorFiltro: (c) => `${c.razon_social} ${c.tipo_documento} ${c.numero_documento}`,
                            valorOrden: (c) => c.razon_social,
                            celda: (c) => (
                                <div>
                                    <p className="font-medium">
                                        {c.razon_social}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {c.tipo_documento} {c.numero_documento}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            encabezado: 'Contacto',
                            valorFiltro: (c) => `${c.telefono ?? ''} ${c.email ?? ''}`,
                            celda: (c) => (
                                <div>
                                    <p>{c.telefono ?? '—'}</p>
                                    <p className="text-muted-foreground">
                                        {c.email ?? ''}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            encabezado: 'Ciudad y ruta',
                            valorFiltro: (c) => `${c.ciudad ?? ''} ${c.ruta ?? ''}`,
                            valorOrden: (c) => c.ciudad ?? '',
                            celda: (c) => (
                                <div>
                                    <p>{c.ciudad ?? '—'}</p>
                                    <p className="text-muted-foreground">
                                        {c.ruta ?? 'Sin ruta'}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            encabezado: 'Tipo',
                            filtro: 'lista',
                            valorFiltro: (c) => etiquetaDe(TIPOS_CLIENTE, c.tipo_cliente),
                            celda: (c) => (
                                <Badge variant="outline">
                                    {etiquetaDe(TIPOS_CLIENTE, c.tipo_cliente)}
                                </Badge>
                            ),
                        },
                        {
                            encabezado: 'Cartera',
                            alinear: 'derecha',
                            valorOrden: (c) => c.saldo_cartera,
                            celda: (c) => moneda.format(c.saldo_cartera),
                        },
                        {
                            encabezado: 'Estado',
                            filtro: 'lista',
                            valorFiltro: (c) =>
                                c.deleted_at ? 'Eliminado' : c.activo ? 'Activo' : 'Inactivo',
                            celda: (c) =>
                                c.deleted_at ? (
                                    <Badge variant="destructive">
                                        Eliminado
                                    </Badge>
                                ) : c.activo ? (
                                    <Badge>Activo</Badge>
                                ) : (
                                    <Badge variant="secondary">Inactivo</Badge>
                                ),
                        },
                    ]}
                    acciones={(cliente) =>
                        cliente.deleted_at ? (
                            can('clientes.eliminar') && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={restore(cliente.id)}
                                        as="button"
                                        preserveScroll
                                    >
                                        Restaurar
                                    </Link>
                                </Button>
                            )
                        ) : (
                            <>
                                {can('clientes.editar') && (
                                    <FormDialog
                                        titulo={`Editar ${cliente.razon_social}`}
                                        accion={ClienteController.update.form(
                                            cliente.id,
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
                                            <ClienteFields
                                                cliente={cliente}
                                                errors={errors}
                                                rutas={rutas}
                                                comerciales={comerciales}
                                            />
                                        )}
                                    </FormDialog>
                                )}
                                {can('clientes.eliminar') && (
                                    <ConfirmDeleteDialog
                                        titulo={`¿Eliminar a ${cliente.razon_social}?`}
                                        descripcion="No se puede eliminar si tiene pedidos pendientes de pago. Podrás restaurarlo desde 'Ver eliminados'."
                                        accion={ClienteController.destroy.form(
                                            cliente.id,
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

ClientesIndex.layout = {
    breadcrumbs: [{ title: 'Clientes', href: index() }],
};
