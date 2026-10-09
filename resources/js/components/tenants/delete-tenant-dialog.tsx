import { Form } from '@inertiajs/react';
import TenantController from '@/actions/App/Http/Controllers/Admin/TenantController';
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
import type { Tenant } from '@/types';

export default function DeleteTenantDialog({ tenant }: { tenant: Tenant }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant="destructive"
                    size="sm"
                    data-test={`eliminar-tenant-${tenant.id}`}
                >
                    Eliminar
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>¿Eliminar a {tenant.nombre}?</DialogTitle>
                <DialogDescription>
                    El tenant dejará de aparecer en el listado, pero sus datos
                    se conservan y podrás restaurarlo desde &quot;Ver
                    eliminados&quot;.
                </DialogDescription>

                <Form
                    {...TenantController.destroy.form(tenant.id)}
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    Cancelar
                                </Button>
                            </DialogClose>
                            <Button variant="destructive" disabled={processing}>
                                Eliminar tenant
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
