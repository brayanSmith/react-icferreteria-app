import type { ComponentProps } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { ESTILO_CELDA } from './estilo-celda';

type TextFieldProps = Omit<ComponentProps<'input'>, 'id'> & {
    /** Se usa como `name` y, con el prefijo "campo-", como `id` del input. */
    name: string;
    etiqueta: string;
    /** Oculta la etiqueta a la vista pero la deja para lectores de pantalla (filas repetidas). */
    etiquetaOculta?: boolean;
    /** Mensaje de error de validación que devuelve el servidor. */
    error?: string;
    /** Texto de ayuda bajo el campo. */
    ayuda?: string;
    /** Aspecto de celda de hoja de cálculo: sin borde visible (para tablas editables). */
    plano?: boolean;
    /** Clases extra para el input (por ejemplo, alinear el texto a la derecha). */
    inputClassName?: string;
};

/**
 * Campo de formulario completo: etiqueta, input, ayuda y error. Evita repetir
 * ese bloque en cada formulario de los CRUD.
 */
export default function TextField({
    name,
    etiqueta,
    etiquetaOculta = false,
    error,
    ayuda,
    plano = false,
    inputClassName,
    className,
    ...props
}: TextFieldProps) {
    const id = `campo-${name}`;

    return (
        <div className={cn('grid gap-2', className)}>
            <Label
                htmlFor={id}
                className={etiquetaOculta ? 'sr-only' : undefined}
            >
                {etiqueta}
            </Label>
            <Input
                id={id}
                name={name}
                className={cn(plano && ESTILO_CELDA, inputClassName)}
                {...props}
            />
            {ayuda && <p className="text-sm text-muted-foreground">{ayuda}</p>}
            <InputError message={error} />
        </div>
    );
}
