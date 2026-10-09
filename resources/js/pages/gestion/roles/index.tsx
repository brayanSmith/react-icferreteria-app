import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import RoleDialog from '@/components/roles/role-dialog';
import RolesTable from '@/components/roles/roles-table';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { index } from '@/routes/gestion/roles';
import type { CatalogoModulo, Rol } from '@/types';

type Props = {
    roles: { data: Rol[] };
    catalogo: CatalogoModulo[];
};

export default function RolesIndex({ roles, catalogo }: Props) {
    const can = useCan();

    return (
        <>
            <Head title="Roles" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Roles"
                        description="Agrupa permisos y asígnalos a tu personal"
                    />
                    {can('roles.crear') && (
                        <RoleDialog catalogo={catalogo}>
                            <Button>Nuevo rol</Button>
                        </RoleDialog>
                    )}
                </div>

                <RolesTable roles={roles.data} catalogo={catalogo} />
            </div>
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [{ title: 'Roles', href: index() }],
};
