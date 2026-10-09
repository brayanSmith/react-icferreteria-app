import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import RoleController from '@/actions/App/Http/Controllers/Gestion/RoleController';
import InputError from '@/components/input-error';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CatalogoModulo, Rol } from '@/types';
import PermissionMatrix from './permission-matrix';

type RoleDialogProps = {
    catalogo: CatalogoModulo[];
    /** Si se envía, el modal edita ese rol; si no, crea uno nuevo. */
    rol?: Rol;
    /** Elemento que abre el modal (normalmente un botón). */
    children: ReactNode;
};

/**
 * Modal para crear o editar un rol con su matriz de permisos. Los permisos
 * elegidos viajan como campos ocultos `permissions[]`.
 */
export default function RoleDialog({
    catalogo,
    rol,
    children,
}: RoleDialogProps) {
    const [abierto, setAbierto] = useState(false);
    const [permisos, setPermisos] = useState<string[]>(rol?.permisos ?? []);
    const editando = rol !== undefined;

    const formAction = editando
        ? RoleController.update.form(rol.id)
        : RoleController.store.form();

    return (
        <Dialog
            open={abierto}
            onOpenChange={(nuevoEstado) => {
                setAbierto(nuevoEstado);

                // Un rol nuevo vuelve a empezar sin permisos cada vez que se abre.
                if (nuevoEstado) {
                    setPermisos(rol?.permisos ?? []);
                }
            }}
        >
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>
                        {editando ? `Editar ${rol.name}` : 'Nuevo rol'}
                    </DialogTitle>
                    <DialogDescription>
                        Elige qué puede hacer quien tenga este rol.
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
                            <div className="grid gap-2">
                                <Label htmlFor="rol-nombre">
                                    Nombre del rol
                                </Label>
                                <Input
                                    id="rol-nombre"
                                    name="name"
                                    defaultValue={rol?.name}
                                    placeholder="Vendedor"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Permisos</Label>
                                <PermissionMatrix
                                    catalogo={catalogo}
                                    seleccionados={permisos}
                                    onChange={setPermisos}
                                />
                                {permisos.map((permiso) => (
                                    <input
                                        key={permiso}
                                        type="hidden"
                                        name="permissions[]"
                                        value={permiso}
                                    />
                                ))}
                                <InputError message={errors.permissions} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancelar
                                    </Button>
                                </DialogClose>
                                <Button disabled={processing}>
                                    {editando ? 'Guardar cambios' : 'Crear rol'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
