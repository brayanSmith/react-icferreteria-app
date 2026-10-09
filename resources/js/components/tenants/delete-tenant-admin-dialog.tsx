import { Form } from '@inertiajs/react';
import { useState } from 'react';
import TenantAdminController from '@/actions/App/Http/Controllers/Admin/TenantAdminController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { Tenant, TenantAdmin } from '@/types';

export default function DeleteTenantAdminDialog({
    tenant,
    admin,
}: {
    tenant: Tenant;
    admin: TenantAdmin;
}) {
    const [abierto, setAbierto] = useState(false);

    return (
        <Dialog open={abierto} onOpenChange={setAbierto}>
            <DialogTrigger asChild>
                <Button
                    variant="destructive"
                    size="sm"
                    data-test={`eliminar-admin-${admin.id}`}
                >
                    Eliminar
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>¿Eliminar a {admin.name}?</DialogTitle>
                <DialogDescription>
                    Dejará de poder ingresar a {tenant.nombre}. Su correo y su
                    documento quedan reservados mientras el registro exista.
                </DialogDescription>

                <Form
                    {...TenantAdminController.destroy.form({
                        tenant: tenant.id,
                        admin: admin.id,
                    })}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setAbierto(false)}
                >
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    Cancelar
                                </Button>
                            </DialogClose>
                            <Button variant="destructive" disabled={processing}>
                                Eliminar administrador
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
