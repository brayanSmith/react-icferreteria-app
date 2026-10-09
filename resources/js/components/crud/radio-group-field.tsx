import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { OpcionDeSelect } from './select-field';

type RadioGroupFieldProps = {
    /** Nombre del campo que viaja con el formulario. */
    name: string;
    etiqueta: string;
    opciones: OpcionDeSelect[];
    /** Opción marcada al abrir el formulario. */
    defaultValue?: string | number;
    /** Cuántas opciones por fila en pantallas anchas. */
    columnas?: 2 | 3 | 4;
    error?: string;
};

/**
 * Grupo de botones de radio (una sola opción a la vez) con etiqueta y error.
 *
 * Usa radios nativos del navegador a propósito: viajan con el formulario sin
 * código extra, se manejan con el teclado y son accesibles. Cada opción se
 * pinta como una tarjeta que resalta cuando está marcada. Es la alternativa a
 * `SelectField` cuando hay pocas opciones y conviene verlas todas a la vez.
 */
export default function RadioGroupField({
    name,
    etiqueta,
    opciones,
    defaultValue,
    columnas = 2,
    error,
}: RadioGroupFieldProps) {
    return (
        <fieldset className="grid gap-2">
            <legend className="mb-1 text-sm font-medium">{etiqueta}</legend>

            <div
                className={cn(
                    'grid gap-2',
                    columnas === 2 && 'sm:grid-cols-2',
                    columnas === 3 && 'sm:grid-cols-3',
                    columnas === 4 && 'sm:grid-cols-4',
                )}
            >
                {opciones.map((opcion) => {
                    const id = `campo-${name}-${opcion.valor}`;

                    return (
                        <Label
                            key={opcion.valor}
                            htmlFor={id}
                            className={cn(
                                'flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 font-normal',
                                'has-checked:border-primary has-checked:bg-primary/5',
                                'has-focus-visible:ring-[3px] has-focus-visible:ring-ring/50',
                            )}
                        >
                            <input
                                id={id}
                                type="radio"
                                name={name}
                                value={opcion.valor}
                                defaultChecked={
                                    String(opcion.valor) ===
                                    String(defaultValue)
                                }
                                className="size-4 accent-primary"
                            />
                            {opcion.etiqueta}
                        </Label>
                    );
                })}
            </div>

            <InputError message={error} />
        </fieldset>
    );
}
