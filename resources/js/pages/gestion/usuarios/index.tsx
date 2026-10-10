import { Head } from '@inertiajs/react';
import ExportarMenu from '@/components/crud/exportar-menu';
import ImportacionesPanel from '@/components/crud/importaciones-panel';
import ImportarDialog from '@/components/crud/importar-dialog';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import UserDialog from '@/components/usuarios/user-dialog';
import UsersTable from '@/components/usuarios/users-table';
import { useCan } from '@/hooks/use-can';
import { index } from '@/routes/gestion/usuarios';
import type {
    Importacion,
    ImportacionPendiente,
    RolOpcion,
    UsuarioTenant,
} from '@/types';

type Props = {
    usuarios: { data: UsuarioTenant[] };
    roles: RolOpcion[];
    importaciones: { data: Importacion[] };
    importacionPendiente: ImportacionPendiente | null;
};

export default function UsuariosIndex({
    usuarios,
    roles,
    importaciones,
    importacionPendiente,
}: Props) {
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
                    <div className="flex flex-wrap gap-2">
                        <ExportarMenu modulo="usuarios" />
                        {can('usuarios.crear') && (
                            <>
                                <ImportarDialog
                                    tipos={[
                                        {
                                            valor: 'usuarios',
                                            etiqueta: 'Usuarios',
                                        },
                                    ]}
                                    pendiente={importacionPendiente}
                                />
                                <UserDialog roles={roles}>
                                    <Button>Nuevo usuario</Button>
                                </UserDialog>
                            </>
                        )}
                    </div>
                </div>

                <ImportacionesPanel importaciones={importaciones.data} />

                <UsersTable usuarios={usuarios.data} roles={roles} />
            </div>
        </>
    );
}

UsuariosIndex.layout = {
    breadcrumbs: [{ title: 'Usuarios', href: index() }],
};
