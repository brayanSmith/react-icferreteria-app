import { useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { Empresa } from '@/types';
import EmpresaForm from './empresa-form';

type EmpresaDialogProps = {
    /** Empresa guardada; null si el tenant aún no la ha registrado. */
    empresa: Empresa | null;
    nombreSugerido: string;
    /** Elemento que abre el modal (normalmente un botón). */
    children: ReactNode;
};

/**
 * Modal con el formulario de la empresa. Los datos solo se pueden modificar
 * desde aquí, para que nadie los cambie por accidente al entrar a la página.
 * Al cerrarse se descarta lo escrito.
 */
export default function EmpresaDialog({
    empresa,
    nombreSugerido,
    children,
}: EmpresaDialogProps) {
    const [abierto, setAbierto] = useState(false);
    const nueva = empresa === null;

    return (
        <Dialog open={abierto} onOpenChange={setAbierto}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>
                        {nueva ? 'Agregar empresa' : 'Editar empresa'}
                    </DialogTitle>
                    <DialogDescription>
                        {nueva
                            ? 'Registra los datos de tu empresa.'
                            : 'Los cambios se aplican en cuanto guardes.'}
                    </DialogDescription>
                </DialogHeader>

                <EmpresaForm
                    empresa={empresa}
                    nombreSugerido={nombreSugerido}
                    onSuccess={() => setAbierto(false)}
                    cancelar={
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancelar
                            </Button>
                        </DialogClose>
                    }
                />
            </DialogContent>
        </Dialog>
    );
}
