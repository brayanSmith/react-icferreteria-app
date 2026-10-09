import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import type { RolOpcion, UsuarioTenant } from '@/types';
import DeleteUserDialog from './delete-user-dialog';
import UserDialog from './user-dialog';

export default function UsersTable({
    usuarios,
    roles,
}: {
    usuarios: UsuarioTenant[];
    roles: RolOpcion[];
}) {
    const can = useCan();

    if (usuarios.length === 0) {
        return (
            <p className="rounded-lg border p-8 text-center text-sm text-muted-foreground">
                Todavía no hay usuarios. Crea el primero para darle acceso a tu
                personal.
            </p>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border">
            <table className="w-full text-sm">
                <thead className="bg-muted/50 text-left">
                    <tr>
                        <th className="px-4 py-3 font-medium">Nombre</th>
                        <th className="px-4 py-3 font-medium">Correo</th>
                        <th className="px-4 py-3 font-medium">Documento</th>
                        <th className="px-4 py-3 font-medium">Roles</th>
                        <th className="px-4 py-3 font-medium">Estado</th>
                        <th className="px-4 py-3 text-right font-medium">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {usuarios.map((usuario) => (
                        <tr key={usuario.id}>
                            <td className="px-4 py-3 font-medium">
                                {usuario.name}
                            </td>
                            <td className="px-4 py-3 text-muted-foreground">
                                {usuario.email}
                            </td>
                            <td className="px-4 py-3 text-muted-foreground">
                                {usuario.n_documento}
                            </td>
                            <td className="px-4 py-3">
                                <div className="flex flex-wrap gap-1">
                                    {usuario.roles.length === 0 ? (
                                        <span className="text-muted-foreground">
                                            Sin roles
                                        </span>
                                    ) : (
                                        usuario.roles.map((rol) => (
                                            <Badge
                                                key={rol.id}
                                                variant="secondary"
                                            >
                                                {rol.name}
                                            </Badge>
                                        ))
                                    )}
                                </div>
                            </td>
                            <td className="px-4 py-3">
                                <div className="flex flex-wrap gap-1">
                                    {usuario.activo ? (
                                        <Badge>Activo</Badge>
                                    ) : (
                                        <Badge variant="outline">
                                            Inactivo
                                        </Badge>
                                    )}
                                    {usuario.debe_cambiar_password && (
                                        <Badge variant="secondary">
                                            Clave temporal
                                        </Badge>
                                    )}
                                </div>
                            </td>
                            <td className="px-4 py-3">
                                <div className="flex justify-end gap-2">
                                    {can('usuarios.editar') && (
                                        <UserDialog
                                            roles={roles}
                                            usuario={usuario}
                                        >
                                            <Button size="sm" variant="outline">
                                                Editar
                                            </Button>
                                        </UserDialog>
                                    )}
                                    {can('usuarios.eliminar') && (
                                        <DeleteUserDialog usuario={usuario} />
                                    )}
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
