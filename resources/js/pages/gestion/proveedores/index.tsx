import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import ProveedorController from '@/actions/App/Http/Controllers/Gestion/ProveedorController';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import CrudTable from '@/components/crud/crud-table';
import FormDialog from '@/components/crud/form-dialog';
import EliminadosToggle from '@/components/crud/eliminados-toggle';
import PageHeader from '@/components/crud/page-header';
import ProveedorFields from '@/components/proveedores/proveedor-fields';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { TIPOS_PROVEEDOR, etiquetaDe } from '@/lib/catalogos';
import { index, restore } from '@/routes/gestion/proveedores';
import type { Proveedor } from '@/types';

type Props = {
    proveedores: { data: Proveedor[] };
};

/**
 * Proveedores del tenant, con búsqueda y paginación. Se eliminan de forma
 * lógica y se restauran desde "Ver eliminados".
 */
export default function ProveedoresIndex({ proveedores }: Props) {
    const can = useCan();
    const [verEliminados, setVerEliminados] = useState(false);
    const filas = useMemo(
        () =>
            proveedores.data.filter(
                (proveedor) => (proveedor.deleted_at !== null) === verEliminados,
            ),
        [proveedores.data, verEliminados],
    );

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

                <CrudTable
                    filas={filas}
                    busqueda={{
                        placeholder: 'Buscar por nombre, razón social o NIT',
                        texto: (p) =>
                            [p.nombre_proveedor, p.razon_social_proveedor, p.nit_proveedor].join(' '),
                    }}
                    barra={
                        <EliminadosToggle
                            id="ver-eliminados"
                            etiqueta="Ver eliminados"
                            activo={verEliminados}
                            onCambiar={setVerEliminados}
                        />
                    }
                    clave={(proveedor) => proveedor.id}
                    vacio={
                        verEliminados
                            ? 'No hay proveedores eliminados.'
                            : 'No hay proveedores que coincidan.'
                    }
                    columnas={[
                        {
                            encabezado: 'Proveedor',
                            valorOrden: (p) => p.nombre_proveedor,
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
                        {
                            encabezado: 'NIT',
                            celda: (p) => p.nit_proveedor,
                            valorOrden: (p) => p.nit_proveedor,
                        },
                        {
                            encabezado: 'Tipo',
                            filtro: 'lista',
                            valorFiltro: (p) => etiquetaDe(TIPOS_PROVEEDOR, p.tipo_proveedor),
                            celda: (p) =>
                                etiquetaDe(TIPOS_PROVEEDOR, p.tipo_proveedor),
                        },
                        {
                            encabezado: 'Ciudad',
                            valorFiltro: (p) => p.ciudad_proveedor ?? '',
                            celda: (p) => p.ciudad_proveedor ?? '—',
                        },
                        {
                            encabezado: 'Teléfono',
                            valorFiltro: (p) => p.telefono_proveedor ?? '',
                            celda: (p) => p.telefono_proveedor ?? '—',
                        },
                        {
                            encabezado: 'Estado',
                            filtro: 'lista',
                            valorFiltro: (p) => (p.deleted_at ? 'Eliminado' : 'Activo'),
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
            </div>
        </>
    );
}

ProveedoresIndex.layout = {
    breadcrumbs: [{ title: 'Proveedores', href: index() }],
};
