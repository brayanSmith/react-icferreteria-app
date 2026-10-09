import CrudTable from '@/components/crud/crud-table';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import type { CatalogoModulo, Rol } from '@/types';
import DeleteRoleDialog from './delete-role-dialog';
import RoleDialog from './role-dialog';

/**
 * Roles del tenant. Las acciones de editar y eliminar solo aparecen si la
 * persona tiene el permiso correspondiente.
 */
export default function RolesTable({
    roles,
    catalogo,
}: {
    roles: Rol[];
    catalogo: CatalogoModulo[];
}) {
    const can = useCan();

    return (
        <CrudTable
            filas={roles}
            clave={(rol) => rol.id}
            vacio="Todavía no hay roles. Crea el primero para asignar permisos a tu personal."
            columnas={[
                {
                    encabezado: 'Rol',
                    valorOrden: (r) => r.name,
                    celda: (r) => <span className="font-medium">{r.name}</span>,
                },
                {
                    encabezado: 'Permisos',
                    alinear: 'derecha',
                    valorOrden: (r) => r.permisos.length,
                    celda: (r) => r.permisos.length,
                },
                {
                    encabezado: 'Usuarios',
                    alinear: 'derecha',
                    valorOrden: (r) => r.users_count ?? 0,
                    celda: (r) => r.users_count ?? 0,
                },
            ]}
            acciones={(rol) => (
                <>
                    {can('roles.editar') && (
                        <RoleDialog catalogo={catalogo} rol={rol}>
                            <Button size="sm" variant="outline">
                                Editar
                            </Button>
                        </RoleDialog>
                    )}
                    {can('roles.eliminar') && <DeleteRoleDialog rol={rol} />}
                </>
            )}
        />
    );
}
