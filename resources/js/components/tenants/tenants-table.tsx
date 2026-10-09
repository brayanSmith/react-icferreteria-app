import { Link } from '@inertiajs/react';
import CrudTable from '@/components/crud/crud-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { store as restaurar } from '@/routes/admin/restored-tenants';
import { edit } from '@/routes/admin/tenants';
import type { Tenant } from '@/types';
import DeleteTenantDialog from './delete-tenant-dialog';

/** Estado del tenant: eliminado, activo o inactivo. */
function EstadoBadge({ tenant }: { tenant: Tenant }) {
    if (tenant.deleted_at) {
        return <Badge variant="destructive">Eliminado</Badge>;
    }

    return tenant.activo ? (
        <Badge>Activo</Badge>
    ) : (
        <Badge variant="secondary">Inactivo</Badge>
    );
}

/**
 * Listado de tenants. La paginación y la búsqueda las hace el servidor, por eso
 * las columnas no se ordenan en el navegador.
 */
export default function TenantsTable({ tenants }: { tenants: Tenant[] }) {
    return (
        <CrudTable
            filas={tenants}
            clave={(tenant) => tenant.id}
            vacio="No hay tenants que coincidan con la búsqueda."
            columnas={[
                {
                    encabezado: 'Nombre',
                    celda: (t) => (
                        <span className="font-medium">{t.nombre}</span>
                    ),
                },
                {
                    encabezado: 'Slug',
                    celda: (t) => (
                        <span className="text-muted-foreground">{t.slug}</span>
                    ),
                },
                {
                    encabezado: 'Estado',
                    celda: (t) => <EstadoBadge tenant={t} />,
                },
                {
                    encabezado: 'Usuarios',
                    alinear: 'derecha',
                    celda: (t) => t.users_count ?? 0,
                },
                {
                    encabezado: 'Creado',
                    celda: (t) => (
                        <span className="text-muted-foreground">
                            {t.created_at
                                ? new Date(t.created_at).toLocaleDateString(
                                      'es-CO',
                                  )
                                : '—'}
                        </span>
                    ),
                },
            ]}
            acciones={(tenant) =>
                tenant.deleted_at ? (
                    <Button asChild size="sm" variant="outline">
                        <Link
                            href={restaurar(tenant.id)}
                            as="button"
                            preserveScroll
                        >
                            Restaurar
                        </Link>
                    </Button>
                ) : (
                    <>
                        <Button asChild size="sm" variant="outline">
                            <Link href={edit(tenant.id)}>Editar</Link>
                        </Button>
                        <DeleteTenantDialog tenant={tenant} />
                    </>
                )
            }
        />
    );
}
