import { Form } from '@inertiajs/react';
import { useState } from 'react';
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
import type { RouteFormDefinition } from '@/wayfinder';

type ConfirmDeleteDialogProps = {
    titulo: string;
    descripcion: string;
    /** Acción de Wayfinder de borrado, por ejemplo `MedidaController.destroy.form(id)`. */
    accion: RouteFormDefinition<'post'>;
    /** Texto del botón que confirma (por defecto "Eliminar"). */
    etiquetaConfirmar?: string;
    /** Marca para localizar el botón en pruebas de navegador. */
    dataTest?: string;
};

/**
 * Modal de confirmación para eliminar un registro. Los CRUD lo reutilizan:
 * solo cambian el texto y la acción. Si el servidor rechaza la eliminación
 * (porque el registro se usa), muestra el motivo en un aviso y el registro
 * se queda donde estaba.
 */
export default function ConfirmDeleteDialog({
    titulo,
    descripcion,
    accion,
    etiquetaConfirmar = 'Eliminar',
    dataTest,
}: ConfirmDeleteDialogProps) {
    const [abierto, setAbierto] = useState(false);

    return (
        <Dialog open={abierto} onOpenChange={setAbierto}>
            <DialogTrigger asChild>
                <Button variant="destructive" size="sm" data-test={dataTest}>
                    Eliminar
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{titulo}</DialogTitle>
                <DialogDescription>{descripcion}</DialogDescription>

                <Form
                    {...accion}
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
                                {etiquetaConfirmar}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
