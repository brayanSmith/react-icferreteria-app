import type { ComponentProps } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type TextFieldProps = Omit<ComponentProps<'input'>, 'id'> & {
    /** Se usa como `name` y, con el prefijo "campo-", como `id` del input. */
    name: string;
    etiqueta: string;
    /** Mensaje de error de validación que devuelve el servidor. */
    error?: string;
    /** Texto de ayuda bajo el campo. */
    ayuda?: string;
};

/**
 * Campo de formulario completo: etiqueta, input, ayuda y error. Evita repetir
 * ese bloque en cada formulario de los CRUD.
 */
export default function TextField({
    name,
    etiqueta,
    error,
    ayuda,
    className,
    ...props
}: TextFieldProps) {
    const id = `campo-${name}`;

    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={id}>{etiqueta}</Label>
            <Input id={id} name={name} {...props} />
            {ayuda && <p className="text-sm text-muted-foreground">{ayuda}</p>}
            <InputError message={error} />
        </div>
    );
}
