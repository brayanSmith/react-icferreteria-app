import { Head, Link } from '@inertiajs/react';
import ClienteController from '@/actions/App/Http/Controllers/Gestion/ClienteController';
import ClienteFields from '@/components/clientes/cliente-fields';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import CrudTable from '@/components/crud/crud-table';
import FormDialog from '@/components/crud/form-dialog';
import ListFilters from '@/components/crud/list-filters';
import PageHeader from '@/components/crud/page-header';
import PaginationFooter from '@/components/crud/pagination-footer';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { TIPOS_CLIENTE, etiquetaDe } from '@/lib/catalogos';
import { index, restore } from '@/routes/gestion/clientes';
import type { Cliente, OpcionSelect, Paginated } from '@/types';

type Props = {
    clientes: Paginated<Cliente>;
    filtros: { buscar: string; eliminados: boolean };
    rutas: OpcionSelect[];
    comerciales: OpcionSelect[];
};

const moneda = new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
});

/**
 * Clientes del tenant, con búsqueda y paginación. Se eliminan de forma lógica y
 * se restauran desde "Ver eliminados".
 */
export default function ClientesIndex({
    clientes,
    filtros,
    rutas,
    comerciales,
}: Props) {
    const can = useCan();

    return (
        <>
            <Head title="Clientes" />

            <div className="space-y-6 p-4">
                <PageHeader
                    titulo="Clientes"
                    descripcion="Personas y empresas a las que les vendes"
                    accion={
                        can('clientes.crear') && (
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
                        )
                    }
                />

                <ListFilters
                    url={index.url()}
                    buscar={filtros.buscar}
                    placeholder="Buscar por nombre, documento, correo o teléfono"
                    eliminados={{
                        parametro: 'eliminados',
                        etiqueta: 'Ver eliminados',
                        activo: filtros.eliminados,
                    }}
                />

                <CrudTable
                    filas={clientes.data}
                    clave={(cliente) => cliente.id}
                    vacio={
                        filtros.eliminados
                            ? 'No hay clientes eliminados.'
                            : 'No hay clientes que coincidan.'
                    }
                    columnas={[
                        { encabezado: 'N°', celda: (c) => c.numero },
                        {
                            encabezado: 'Cliente',
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
                            celda: (c) => (
                                <Badge variant="outline">
                                    {etiquetaDe(TIPOS_CLIENTE, c.tipo_cliente)}
                                </Badge>
                            ),
                        },
                        {
                            encabezado: 'Cartera',
                            alinear: 'derecha',
                            celda: (c) => moneda.format(c.saldo_cartera),
                        },
                        {
                            encabezado: 'Estado',
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

                <PaginationFooter datos={clientes} />
            </div>
        </>
    );
}

ClientesIndex.layout = {
    breadcrumbs: [{ title: 'Clientes', href: index() }],
};
