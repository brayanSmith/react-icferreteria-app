import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import type { CatalogoModulo, Rol } from '@/types';
import DeleteRoleDialog from './delete-role-dialog';
import RoleDialog from './role-dialog';

export default function RolesTable({
    roles,
    catalogo,
}: {
    roles: Rol[];
    catalogo: CatalogoModulo[];
}) {
    const can = useCan();

    if (roles.length === 0) {
        return (
            <p className="rounded-lg border p-8 text-center text-sm text-muted-foreground">
                Todavía no hay roles. Crea el primero para asignar permisos a tu
                personal.
            </p>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border">
            <table className="w-full text-sm">
                <thead className="bg-muted/50 text-left">
                    <tr>
                        <th className="px-4 py-3 font-medium">Rol</th>
                        <th className="px-4 py-3 text-right font-medium">
                            Permisos
                        </th>
                        <th className="px-4 py-3 text-right font-medium">
                            Usuarios
                        </th>
                        <th className="px-4 py-3 text-right font-medium">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {roles.map((rol) => (
                        <tr key={rol.id}>
                            <td className="px-4 py-3 font-medium">
                                {rol.name}
                            </td>
                            <td className="px-4 py-3 text-right">
                                {rol.permisos.length}
                            </td>
                            <td className="px-4 py-3 text-right">
                                {rol.users_count ?? 0}
                            </td>
                            <td className="px-4 py-3">
                                <div className="flex justify-end gap-2">
                                    {can('roles.editar') && (
                                        <RoleDialog
                                            catalogo={catalogo}
                                            rol={rol}
                                        >
                                            <Button size="sm" variant="outline">
                                                Editar
                                            </Button>
                                        </RoleDialog>
                                    )}
                                    {can('roles.eliminar') && (
                                        <DeleteRoleDialog rol={rol} />
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
