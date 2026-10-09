import { Form } from '@inertiajs/react';
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
import { cn } from '@/lib/utils';
import type { RouteFormDefinition } from '@/wayfinder';

type FormDialogProps = {
    titulo: string;
    descripcion?: string;
    /** Acción de Wayfinder, por ejemplo `MedidaController.store.form()`. */
    accion: RouteFormDefinition<'post'>;
    etiquetaGuardar: string;
    /** Ancho del modal: `md` para formularios cortos, `xl` para los largos. */
    ancho?: 'md' | 'xl';
    /** Elemento que abre el modal (normalmente un botón). */
    trigger: ReactNode;
    /** Campos del formulario; recibe los errores de validación del servidor. */
    children: (estado: { errors: Record<string, string> }) => ReactNode;
};

/**
 * Modal con un formulario de Inertia: lo usan todos los CRUD para crear y
 * editar. Se cierra solo al guardar con éxito y, al cerrarse, descarta lo
 * escrito (el contenido se desmonta).
 */
export default function FormDialog({
    titulo,
    descripcion,
    accion,
    etiquetaGuardar,
    ancho = 'md',
    trigger,
    children,
}: FormDialogProps) {
    const [abierto, setAbierto] = useState(false);

    return (
        <Dialog open={abierto} onOpenChange={setAbierto}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent
                className={cn(
                    'max-h-[90vh] overflow-y-auto',
                    ancho === 'xl' ? 'sm:max-w-3xl' : 'sm:max-w-lg',
                )}
            >
                <DialogHeader>
                    <DialogTitle>{titulo}</DialogTitle>
                    {descripcion && (
                        <DialogDescription>{descripcion}</DialogDescription>
                    )}
                </DialogHeader>

                <Form
                    {...accion}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setAbierto(false)}
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            {children({ errors })}
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancelar
                                    </Button>
                                </DialogClose>
                                <Button disabled={processing}>
                                    {etiquetaGuardar}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
