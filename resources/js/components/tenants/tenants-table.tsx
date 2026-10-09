import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { store as restaurar } from '@/routes/admin/restored-tenants';
import { edit } from '@/routes/admin/tenants';
import type { Tenant } from '@/types';
import DeleteTenantDialog from './delete-tenant-dialog';

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

function Acciones({ tenant }: { tenant: Tenant }) {
    if (tenant.deleted_at) {
        return (
            <Button asChild size="sm" variant="outline">
                <Link href={restaurar(tenant.id)} as="button" preserveScroll>
                    Restaurar
                </Link>
            </Button>
        );
    }

    return (
        <div className="flex justify-end gap-2">
            <Button asChild size="sm" variant="outline">
                <Link href={edit(tenant.id)}>Editar</Link>
            </Button>
            <DeleteTenantDialog tenant={tenant} />
        </div>
    );
}

export default function TenantsTable({ tenants }: { tenants: Tenant[] }) {
    if (tenants.length === 0) {
        return (
            <p className="rounded-lg border p-8 text-center text-sm text-muted-foreground">
                No hay tenants que coincidan con la búsqueda.
            </p>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border">
            <table className="w-full text-sm">
                <thead className="bg-muted/50 text-left">
                    <tr>
                        <th className="px-4 py-3 font-medium">Nombre</th>
                        <th className="px-4 py-3 font-medium">Slug</th>
                        <th className="px-4 py-3 font-medium">Estado</th>
                        <th className="px-4 py-3 text-right font-medium">
                            Usuarios
                        </th>
                        <th className="px-4 py-3 font-medium">Creado</th>
                        <th className="px-4 py-3 text-right font-medium">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {tenants.map((tenant) => (
                        <tr key={tenant.id}>
                            <td className="px-4 py-3 font-medium">
                                {tenant.nombre}
                            </td>
                            <td className="px-4 py-3 text-muted-foreground">
                                {tenant.slug}
                            </td>
                            <td className="px-4 py-3">
                                <EstadoBadge tenant={tenant} />
                            </td>
                            <td className="px-4 py-3 text-right">
                                {tenant.users_count ?? 0}
                            </td>
                            <td className="px-4 py-3 text-muted-foreground">
                                {tenant.created_at
                                    ? new Date(
                                          tenant.created_at,
                                      ).toLocaleDateString('es-CO')
                                    : '—'}
                            </td>
                            <td className="px-4 py-3 text-right">
                                <Acciones tenant={tenant} />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
