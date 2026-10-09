import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { ESTILO_CELDA } from './estilo-celda';
import SearchableSelect from './searchable-select';

export type OpcionDeSelect = { valor: string | number; etiqueta: string };

/**
 * A partir de cuántas opciones el selector pasa a tener buscador. Con pocas
 * opciones un desplegable simple es más rápido; con más, buscar escribiendo
 * evita recorrer la lista.
 */
export const UMBRAL_BUSQUEDA = 10;

type SelectFieldProps = {
    /** Se usa como `name` y, con el prefijo "campo-", como `id` del selector. */
    name: string;
    etiqueta: string;
    /** Oculta la etiqueta a la vista pero la deja para lectores de pantalla (filas repetidas). */
    etiquetaOculta?: boolean;
    opciones: OpcionDeSelect[];
    /** Texto de la primera opción vacía (por ejemplo, "Selecciona una medida"). */
    placeholder?: string;
    error?: string;
    className?: string;
    /** Valor inicial cuando el campo no lo controla el padre. */
    defaultValue?: string | number;
    /** Valor actual, para encadenar selects dependientes. Va junto a `onValueChange`. */
    value?: string | number;
    /** Se llama con el valor elegido, sea cual sea el tipo de selector. */
    onValueChange?: (valor: string) => void;
    disabled?: boolean;
    required?: boolean;
    /** Aspecto de celda de hoja de cálculo: sin borde visible (para tablas editables). */
    plano?: boolean;
};

/**
 * Campo de selección con etiqueta y error.
 *
 * Elige solo el tipo de selector según la cantidad de opciones: hasta
 * `UMBRAL_BUSQUEDA` usa el `<select>` del navegador (viaja con el formulario sin
 * código extra y funciona igual en móvil); con más opciones usa un selector con
 * buscador. Ambos aceptan el valor sin controlar (`defaultValue`) o controlado
 * (`value` + `onValueChange`).
 */
export default function SelectField({
    name,
    etiqueta,
    etiquetaOculta = false,
    opciones,
    placeholder,
    error,
    className,
    defaultValue,
    value,
    onValueChange,
    disabled,
    required,
    plano = false,
}: SelectFieldProps) {
    const id = `campo-${name}`;
    const conBuscador = opciones.length > UMBRAL_BUSQUEDA;

    return (
        <div className={cn('grid gap-2', className)}>
            <Label
                htmlFor={id}
                className={etiquetaOculta ? 'sr-only' : undefined}
            >
                {etiqueta}
            </Label>

            {conBuscador ? (
                <SearchableSelect
                    id={id}
                    name={name}
                    opciones={opciones}
                    value={value}
                    defaultValue={defaultValue}
                    onValueChange={onValueChange}
                    placeholder={placeholder}
                    permitirVacio={placeholder !== undefined}
                    disabled={disabled}
                    invalid={error !== undefined}
                    plano={plano}
                />
            ) : (
                <select
                    id={id}
                    name={name}
                    value={value}
                    defaultValue={
                        value === undefined ? defaultValue : undefined
                    }
                    onChange={(evento) => onValueChange?.(evento.target.value)}
                    disabled={disabled}
                    required={required}
                    className={cn(
                        'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none',
                        'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                        plano && ESTILO_CELDA,
                    )}
                >
                    {placeholder !== undefined && (
                        <option value="">{placeholder}</option>
                    )}
                    {opciones.map((opcion) => (
                        <option key={opcion.valor} value={opcion.valor}>
                            {opcion.etiqueta}
                        </option>
                    ))}
                </select>
            )}

            <InputError message={error} />
        </div>
    );
}
