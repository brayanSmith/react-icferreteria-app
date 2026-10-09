import CrudTable from '@/components/crud/crud-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import type { RolOpcion, UsuarioTenant } from '@/types';
import DeleteUserDialog from './delete-user-dialog';
import UserDialog from './user-dialog';

/**
 * Personal del tenant. Llega completo desde el servidor, así que las columnas
 * principales se pueden ordenar en el navegador.
 */
export default function UsersTable({
    usuarios,
    roles,
}: {
    usuarios: UsuarioTenant[];
    roles: RolOpcion[];
}) {
    const can = useCan();

    return (
        <CrudTable
            filas={usuarios}
            clave={(usuario) => usuario.id}
            vacio="Todavía no hay usuarios. Crea el primero para darle acceso a tu personal."
            columnas={[
                {
                    encabezado: 'Nombre',
                    valorOrden: (u) => u.name,
                    celda: (u) => <span className="font-medium">{u.name}</span>,
                },
                {
                    encabezado: 'Correo',
                    valorOrden: (u) => u.email,
                    celda: (u) => (
                        <span className="text-muted-foreground">{u.email}</span>
                    ),
                },
                {
                    encabezado: 'Documento',
                    valorOrden: (u) => u.n_documento,
                    celda: (u) => (
                        <span className="text-muted-foreground">
                            {u.n_documento}
                        </span>
                    ),
                },
                {
                    encabezado: 'Roles',
                    celda: (u) =>
                        u.roles.length === 0 ? (
                            <span className="text-muted-foreground">
                                Sin roles
                            </span>
                        ) : (
                            <div className="flex flex-wrap gap-1">
                                {u.roles.map((rol) => (
                                    <Badge key={rol.id} variant="secondary">
                                        {rol.name}
                                    </Badge>
                                ))}
                            </div>
                        ),
                },
                {
                    encabezado: 'Estado',
                    celda: (u) => (
                        <div className="flex flex-wrap gap-1">
                            {u.activo ? (
                                <Badge>Activo</Badge>
                            ) : (
                                <Badge variant="outline">Inactivo</Badge>
                            )}
                            {u.debe_cambiar_password && (
                                <Badge variant="secondary">
                                    Clave temporal
                                </Badge>
                            )}
                        </div>
                    ),
                },
            ]}
            acciones={(usuario) => (
                <>
                    {can('usuarios.editar') && (
                        <UserDialog roles={roles} usuario={usuario}>
                            <Button size="sm" variant="outline">
                                Editar
                            </Button>
                        </UserDialog>
                    )}
                    {can('usuarios.eliminar') && (
                        <DeleteUserDialog usuario={usuario} />
                    )}
                </>
            )}
        />
    );
}
