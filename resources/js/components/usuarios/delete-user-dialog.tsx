import { Form } from '@inertiajs/react';
import { useState } from 'react';
import UserController from '@/actions/App/Http/Controllers/Gestion/UserController';
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
import type { UsuarioTenant } from '@/types';

export default function DeleteUserDialog({
    usuario,
}: {
    usuario: UsuarioTenant;
}) {
    const [abierto, setAbierto] = useState(false);

    return (
        <Dialog open={abierto} onOpenChange={setAbierto}>
            <DialogTrigger asChild>
                <Button
                    variant="destructive"
                    size="sm"
                    data-test={`eliminar-usuario-${usuario.id}`}
                >
                    Eliminar
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>¿Eliminar a {usuario.name}?</DialogTitle>
                <DialogDescription>
                    Dejará de poder ingresar. Su correo y su documento quedan
                    reservados mientras el registro exista.
                </DialogDescription>

                <Form
                    {...UserController.destroy.form(usuario.id)}
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
                                Eliminar usuario
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
