import { Head, Link } from '@inertiajs/react';
import ProveedorController from '@/actions/App/Http/Controllers/Gestion/ProveedorController';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import CrudTable from '@/components/crud/crud-table';
import FormDialog from '@/components/crud/form-dialog';
import ListFilters from '@/components/crud/list-filters';
import PageHeader from '@/components/crud/page-header';
import PaginationFooter from '@/components/crud/pagination-footer';
import ProveedorFields from '@/components/proveedores/proveedor-fields';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { TIPOS_PROVEEDOR, etiquetaDe } from '@/lib/catalogos';
import { index, restore } from '@/routes/gestion/proveedores';
import type { Paginated, Proveedor } from '@/types';

type Props = {
    proveedores: Paginated<Proveedor>;
    filtros: { buscar: string; eliminados: boolean };
};

/**
 * Proveedores del tenant, con búsqueda y paginación. Se eliminan de forma
 * lógica y se restauran desde "Ver eliminados".
 */
export default function ProveedoresIndex({ proveedores, filtros }: Props) {
    const can = useCan();

    return (
        <>
            <Head title="Proveedores" />

            <div className="space-y-6 p-4">
                <PageHeader
                    titulo="Proveedores"
                    descripcion="Empresas y personas a las que les compras"
                    accion={
                        can('proveedores.crear') && (
                            <FormDialog
                                titulo="Nuevo proveedor"
                                accion={ProveedorController.store.form()}
                                etiquetaGuardar="Crear proveedor"
                                ancho="xl"
                                trigger={<Button>Nuevo proveedor</Button>}
                            >
                                {({ errors }) => (
                                    <ProveedorFields errors={errors} />
                                )}
                            </FormDialog>
                        )
                    }
                />

                <ListFilters
                    url={index.url()}
                    buscar={filtros.buscar}
                    placeholder="Buscar por nombre, razón social o NIT"
                    eliminados={{
                        parametro: 'eliminados',
                        etiqueta: 'Ver eliminados',
                        activo: filtros.eliminados,
                    }}
                />

                <CrudTable
                    filas={proveedores.data}
                    clave={(proveedor) => proveedor.id}
                    vacio={
                        filtros.eliminados
                            ? 'No hay proveedores eliminados.'
                            : 'No hay proveedores que coincidan.'
                    }
                    columnas={[
                        {
                            encabezado: 'Proveedor',
                            celda: (p) => (
                                <div>
                                    <p className="font-medium">
                                        {p.nombre_proveedor}
                                    </p>
                                    {p.razon_social_proveedor && (
                                        <p className="text-muted-foreground">
                                            {p.razon_social_proveedor}
                                        </p>
                                    )}
                                </div>
                            ),
                        },
                        { encabezado: 'NIT', celda: (p) => p.nit_proveedor },
                        {
                            encabezado: 'Tipo',
                            celda: (p) =>
                                etiquetaDe(TIPOS_PROVEEDOR, p.tipo_proveedor),
                        },
                        {
                            encabezado: 'Ciudad',
                            celda: (p) => p.ciudad_proveedor ?? '—',
                        },
                        {
                            encabezado: 'Teléfono',
                            celda: (p) => p.telefono_proveedor ?? '—',
                        },
                        {
                            encabezado: 'Estado',
                            celda: (p) =>
                                p.deleted_at ? (
                                    <Badge variant="destructive">
                                        Eliminado
                                    </Badge>
                                ) : (
                                    <Badge>Activo</Badge>
                                ),
                        },
                    ]}
                    acciones={(proveedor) =>
                        proveedor.deleted_at ? (
                            can('proveedores.eliminar') && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={restore(proveedor.id)}
                                        as="button"
                                        preserveScroll
                                    >
                                        Restaurar
                                    </Link>
                                </Button>
                            )
                        ) : (
                            <>
                                {can('proveedores.editar') && (
                                    <FormDialog
                                        titulo={`Editar ${proveedor.nombre_proveedor}`}
                                        accion={ProveedorController.update.form(
                                            proveedor.id,
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
                                            <ProveedorFields
                                                proveedor={proveedor}
                                                errors={errors}
                                            />
                                        )}
                                    </FormDialog>
                                )}
                                {can('proveedores.eliminar') && (
                                    <ConfirmDeleteDialog
                                        titulo={`¿Eliminar a ${proveedor.nombre_proveedor}?`}
                                        descripcion="Solo se puede eliminar si no tiene compras. Podrás restaurarlo desde 'Ver eliminados'."
                                        accion={ProveedorController.destroy.form(
                                            proveedor.id,
                                        )}
                                    />
                                )}
                            </>
                        )
                    }
                />

                <PaginationFooter datos={proveedores} />
            </div>
        </>
    );
}

ProveedoresIndex.layout = {
    breadcrumbs: [{ title: 'Proveedores', href: index() }],
};
