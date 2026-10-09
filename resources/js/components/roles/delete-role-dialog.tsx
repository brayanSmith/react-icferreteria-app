import { Form } from '@inertiajs/react';
import { useState } from 'react';
import RoleController from '@/actions/App/Http/Controllers/Gestion/RoleController';
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
import type { Rol } from '@/types';

export default function DeleteRoleDialog({ rol }: { rol: Rol }) {
    const [abierto, setAbierto] = useState(false);
    const usuarios = rol.users_count ?? 0;

    return (
        <Dialog open={abierto} onOpenChange={setAbierto}>
            <DialogTrigger asChild>
                <Button
                    variant="destructive"
                    size="sm"
                    data-test={`eliminar-rol-${rol.id}`}
                >
                    Eliminar
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>¿Eliminar el rol {rol.name}?</DialogTitle>
                <DialogDescription>
                    {usuarios > 0
                        ? `${usuarios} usuario(s) lo tienen asignado y perderán los permisos que les daba este rol.`
                        : 'Ningún usuario lo tiene asignado.'}
                </DialogDescription>

                <Form
                    {...RoleController.destroy.form(rol.id)}
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
                                Eliminar rol
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
