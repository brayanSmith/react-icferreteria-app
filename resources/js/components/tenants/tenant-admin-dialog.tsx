import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import TenantAdminController from '@/actions/App/Http/Controllers/Admin/TenantAdminController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { Tenant, TenantAdmin } from '@/types';
import UserCredentialsFields from '@/components/user-credentials-fields';

type TenantAdminDialogProps = {
    tenant: Tenant;
    /** Si se envía, el modal edita a ese administrador; si no, crea uno nuevo. */
    admin?: TenantAdmin;
    /** Elemento que abre el modal (normalmente un botón). */
    children: ReactNode;
};

/**
 * Modal para agregar o editar un administrador del tenant. Se cierra solo al
 * guardar y, al desmontarse, deja el formulario limpio para la próxima vez.
 */
export default function TenantAdminDialog({
    tenant,
    admin,
    children,
}: TenantAdminDialogProps) {
    const [abierto, setAbierto] = useState(false);
    const editando = admin !== undefined;

    const formAction = editando
        ? TenantAdminController.update.form({
              tenant: tenant.id,
              admin: admin.id,
          })
        : TenantAdminController.store.form(tenant.id);

    return (
        <Dialog open={abierto} onOpenChange={setAbierto}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {editando
                            ? `Editar a ${admin.name}`
                            : 'Agregar administrador'}
                    </DialogTitle>
                    <DialogDescription>
                        {editando
                            ? 'Actualiza los datos de acceso del administrador.'
                            : `Tendrá acceso completo a ${tenant.nombre}.`}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...formAction}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setAbierto(false)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <UserCredentialsFields
                                errors={errors}
                                valoresIniciales={admin}
                                passwordOpcional={editando}
                            />
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancelar
                                    </Button>
                                </DialogClose>
                                <Button disabled={processing}>
                                    {editando
                                        ? 'Guardar cambios'
                                        : 'Agregar administrador'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
