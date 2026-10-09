import { useState } from 'react';
import type { ReactNode } from 'react';
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
import type { TenantAdminValores } from '@/types';
import UserCredentialsFields from '@/components/user-credentials-fields';

type Props = {
    /** Si se envía, el modal edita ese borrador; si no, arma uno nuevo. */
    borrador?: TenantAdminValores;
    onGuardar: (valores: TenantAdminValores) => void;
    /** Elemento que abre el modal (normalmente un botón). */
    children: ReactNode;
};

const completo = (valores: TenantAdminValores) =>
    Object.values(valores).every((valor) => valor.trim() !== '');

/**
 * Modal para armar un administrador antes de que exista el tenant. No usa un
 * <form>: devuelve los valores al formulario del tenant, que los envía todos
 * juntos al guardar.
 */
export default function TenantAdminDraftDialog({
    borrador,
    onGuardar,
    children,
}: Props) {
    const [abierto, setAbierto] = useState(false);
    const [valores, setValores] = useState<TenantAdminValores>({
        name: borrador?.name ?? '',
        email: borrador?.email ?? '',
        n_documento: borrador?.n_documento ?? '',
        password: borrador?.password ?? '',
    });
    const editando = borrador !== undefined;

    function guardar() {
        onGuardar(valores);
        setAbierto(false);
    }

    return (
        <Dialog
            open={abierto}
            onOpenChange={(nuevoEstado) => {
                setAbierto(nuevoEstado);

                // Un borrador nuevo vuelve a empezar vacío cada vez que se abre.
                if (nuevoEstado && !editando) {
                    setValores({
                        name: '',
                        email: '',
                        n_documento: '',
                        password: '',
                    });
                }
            }}
        >
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {editando
                            ? `Editar a ${borrador.name || 'administrador'}`
                            : 'Agregar administrador'}
                    </DialogTitle>
                    <DialogDescription>
                        Se creará junto con el tenant al guardar.
                    </DialogDescription>
                </DialogHeader>

                <UserCredentialsFields
                    valoresIniciales={valores}
                    onChange={setValores}
                />

                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button type="button" variant="secondary">
                            Cancelar
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        disabled={!completo(valores)}
                        onClick={guardar}
                    >
                        {editando ? 'Guardar cambios' : 'Agregar administrador'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
