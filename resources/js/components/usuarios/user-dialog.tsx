import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import UserController from '@/actions/App/Http/Controllers/Gestion/UserController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import UserCredentialsFields from '@/components/user-credentials-fields';
import type { RolOpcion, UsuarioTenant } from '@/types';

type UserDialogProps = {
    roles: RolOpcion[];
    /** Si se envía, el modal edita a ese usuario; si no, crea uno nuevo. */
    usuario?: UsuarioTenant;
    /** Elemento que abre el modal (normalmente un botón). */
    children: ReactNode;
};

/**
 * Modal para crear o editar personal del tenant, con sus roles. Los roles
 * elegidos viajan como campos ocultos `roles[]`.
 */
export default function UserDialog({
    roles,
    usuario,
    children,
}: UserDialogProps) {
    const [abierto, setAbierto] = useState(false);
    const [rolesElegidos, setRolesElegidos] = useState<number[]>(
        usuario?.roles.map((rol) => rol.id) ?? [],
    );
    const editando = usuario !== undefined;

    const formAction = editando
        ? UserController.update.form(usuario.id)
        : UserController.store.form();

    function alternarRol(id: number, marcar: boolean) {
        setRolesElegidos((actuales) =>
            marcar
                ? [...actuales, id]
                : actuales.filter((actual) => actual !== id),
        );
    }

    return (
        <Dialog
            open={abierto}
            onOpenChange={(nuevoEstado) => {
                setAbierto(nuevoEstado);

                if (nuevoEstado) {
                    setRolesElegidos(usuario?.roles.map((rol) => rol.id) ?? []);
                }
            }}
        >
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {editando
                            ? `Editar a ${usuario.name}`
                            : 'Nuevo usuario'}
                    </DialogTitle>
                    <DialogDescription>
                        {editando
                            ? 'Actualiza sus datos y los roles que tiene asignados.'
                            : 'Podrá ingresar con la contraseña temporal que definas aquí.'}
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
                                valoresIniciales={usuario}
                                passwordOpcional={editando}
                            />

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="user-cod-vendedor">
                                        Código de vendedor (opcional)
                                    </Label>
                                    <Input
                                        id="user-cod-vendedor"
                                        name="cod_vendedor"
                                        defaultValue={
                                            usuario?.cod_vendedor ?? ''
                                        }
                                    />
                                    <InputError message={errors.cod_vendedor} />
                                </div>

                                <div className="flex items-center gap-2 sm:mt-8 sm:h-9">
                                    <Checkbox
                                        id="user-activo"
                                        name="activo"
                                        defaultChecked={usuario?.activo ?? true}
                                    />
                                    <Label htmlFor="user-activo">Activo</Label>
                                </div>
                            </div>

                            <fieldset className="grid gap-3">
                                <legend className="mb-1 text-sm font-medium">
                                    Roles
                                </legend>
                                {roles.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Aún no hay roles. Créalos en la sección
                                        Roles para asignarlos aquí.
                                    </p>
                                ) : (
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        {roles.map((rol) => (
                                            <div
                                                key={rol.id}
                                                className="flex items-center gap-2"
                                            >
                                                <Checkbox
                                                    id={`user-rol-${rol.id}`}
                                                    checked={rolesElegidos.includes(
                                                        rol.id,
                                                    )}
                                                    onCheckedChange={(valor) =>
                                                        alternarRol(
                                                            rol.id,
                                                            valor === true,
                                                        )
                                                    }
                                                />
                                                <Label
                                                    htmlFor={`user-rol-${rol.id}`}
                                                >
                                                    {rol.name}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                {rolesElegidos.map((id) => (
                                    <input
                                        key={id}
                                        type="hidden"
                                        name="roles[]"
                                        value={id}
                                    />
                                ))}
                                <InputError message={errors.roles} />
                            </fieldset>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancelar
                                    </Button>
                                </DialogClose>
                                <Button disabled={processing}>
                                    {editando
                                        ? 'Guardar cambios'
                                        : 'Crear usuario'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
