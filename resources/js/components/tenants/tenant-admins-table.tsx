import CrudTable from '@/components/crud/crud-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Tenant, TenantAdmin } from '@/types';
import DeleteTenantAdminDialog from './delete-tenant-admin-dialog';
import TenantAdminDialog from './tenant-admin-dialog';

/**
 * Administradores de un tenant. Son pocos y llegan completos, así que las
 * columnas principales se pueden ordenar en el navegador.
 */
export default function TenantAdminsTable({
    tenant,
    administradores,
}: {
    tenant: Tenant;
    administradores: TenantAdmin[];
}) {
    return (
        <CrudTable
            filas={administradores}
            clave={(administrador) => administrador.id}
            vacio="Este tenant todavía no tiene administradores."
            columnas={[
                {
                    encabezado: 'Nombre',
                    valorOrden: (a) => a.name,
                    celda: (a) => <span className="font-medium">{a.name}</span>,
                },
                {
                    encabezado: 'Correo',
                    valorOrden: (a) => a.email,
                    celda: (a) => (
                        <span className="text-muted-foreground">{a.email}</span>
                    ),
                },
                {
                    encabezado: 'Documento',
                    valorOrden: (a) => a.n_documento,
                    celda: (a) => (
                        <span className="text-muted-foreground">
                            {a.n_documento}
                        </span>
                    ),
                },
                {
                    encabezado: 'Contraseña',
                    celda: (a) =>
                        a.debe_cambiar_password ? (
                            <Badge variant="secondary">Temporal</Badge>
                        ) : (
                            <Badge variant="outline">Definida</Badge>
                        ),
                },
            ]}
            acciones={(administrador) => (
                <>
                    <TenantAdminDialog tenant={tenant} admin={administrador}>
                        <Button size="sm" variant="outline">
                            Editar
                        </Button>
                    </TenantAdminDialog>
                    <DeleteTenantAdminDialog
                        tenant={tenant}
                        admin={administrador}
                    />
                </>
            )}
        />
    );
}
