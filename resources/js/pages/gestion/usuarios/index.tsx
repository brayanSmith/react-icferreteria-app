import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import UserDialog from '@/components/usuarios/user-dialog';
import UsersTable from '@/components/usuarios/users-table';
import { useCan } from '@/hooks/use-can';
import { index } from '@/routes/gestion/usuarios';
import type { RolOpcion, UsuarioTenant } from '@/types';

type Props = {
    usuarios: { data: UsuarioTenant[] };
    roles: RolOpcion[];
};

export default function UsuariosIndex({ usuarios, roles }: Props) {
    const can = useCan();

    return (
        <>
            <Head title="Usuarios" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Usuarios"
                        description="Personal con acceso a tu empresa"
                    />
                    {can('usuarios.crear') && (
                        <UserDialog roles={roles}>
                            <Button>Nuevo usuario</Button>
                        </UserDialog>
                    )}
                </div>

                <UsersTable usuarios={usuarios.data} roles={roles} />
            </div>
        </>
    );
}

UsuariosIndex.layout = {
    breadcrumbs: [{ title: 'Usuarios', href: index() }],
};
