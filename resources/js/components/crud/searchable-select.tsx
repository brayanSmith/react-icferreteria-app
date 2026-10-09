import { Check, ChevronsUpDown, Search } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { normalizarTexto } from '@/lib/text';
import { cn } from '@/lib/utils';
import { ESTILO_CELDA } from './estilo-celda';
import type { OpcionDeSelect } from './select-field';

type SearchableSelectProps = {
    id: string;
    /** Nombre del campo que viaja con el formulario. */
    name: string;
    opciones: OpcionDeSelect[];
    /** Valor seleccionado, si lo controla el padre. */
    value?: string | number;
    /** Valor inicial cuando no se controla desde fuera. */
    defaultValue?: string | number;
    onValueChange?: (valor: string) => void;
    /** Texto cuando no hay nada elegido. */
    placeholder?: string;
    /** Si es true, la primera entrada de la lista permite dejarlo sin elegir. */
    permitirVacio?: boolean;
    disabled?: boolean;
    invalid?: boolean;
    /** Aspecto de celda de hoja de cálculo: sin borde visible (para tablas editables). */
    plano?: boolean;
};

/**
 * Selector con buscador, para listas largas: se escribe para filtrar y se
 * elige con el ratón o con el teclado (flechas, Enter y Escape).
 *
 * Se comporta como un `<select>`: el valor elegido viaja con el formulario en
 * un campo oculto con el mismo `name`. La lista flota en un portal, así que
 * también funciona dentro de modales sin quedar recortada.
 */
export default function SearchableSelect({
    id,
    name,
    opciones,
    value,
    defaultValue,
    onValueChange,
    placeholder = 'Selecciona una opción',
    permitirVacio = false,
    disabled = false,
    invalid = false,
    plano = false,
}: SearchableSelectProps) {
    const controlado = value !== undefined;
    const [interno, setInterno] = useState(
        defaultValue === undefined ? '' : String(defaultValue),
    );
    const actual = controlado ? String(value) : interno;

    const [abierto, setAbierto] = useState(false);
    const [texto, setTexto] = useState('');
    const [resaltado, setResaltado] = useState(0);
    const listaRef = useRef<HTMLUListElement>(null);

    // Entradas de la lista: la opción vacía (si se permite y no se está buscando) y las que coinciden.
    const entradas = useMemo(() => {
        const coincidentes = opciones.filter((opcion) =>
            normalizarTexto(opcion.etiqueta).includes(normalizarTexto(texto)),
        );

        return permitirVacio && texto === ''
            ? [{ valor: '', etiqueta: placeholder }, ...coincidentes]
            : coincidentes;
    }, [opciones, texto, permitirVacio, placeholder]);

    const seleccionada = opciones.find(
        (opcion) => String(opcion.valor) === actual,
    );

    // Mantiene a la vista la entrada resaltada al moverse con el teclado.
    useEffect(() => {
        const elemento = listaRef.current?.children[resaltado];

        elemento?.scrollIntoView({ block: 'nearest' });
    }, [resaltado, abierto]);

    function elegir(valor: string) {
        if (!controlado) {
            setInterno(valor);
        }

        onValueChange?.(valor);
        setAbierto(false);
    }

    function alCambiarApertura(nuevoEstado: boolean) {
        setAbierto(nuevoEstado);

        if (nuevoEstado) {
            setTexto('');
            // Arranca resaltando la opción ya elegida, si la hay.
            const posicion = permitirVacio
                ? opciones.findIndex(
                      (opcion) => String(opcion.valor) === actual,
                  ) + 1
                : opciones.findIndex(
                      (opcion) => String(opcion.valor) === actual,
                  );

            setResaltado(Math.max(0, posicion));
        }
    }

    function alPulsarTecla(evento: KeyboardEvent<HTMLInputElement>) {
        if (evento.key === 'ArrowDown') {
            evento.preventDefault();
            setResaltado((actualPos) =>
                Math.min(entradas.length - 1, actualPos + 1),
            );
        } else if (evento.key === 'ArrowUp') {
            evento.preventDefault();
            setResaltado((actualPos) => Math.max(0, actualPos - 1));
        } else if (evento.key === 'Enter') {
            // Enter elige y no envía el formulario.
            evento.preventDefault();

            if (entradas[resaltado]) {
                elegir(String(entradas[resaltado].valor));
            }
        }
    }

    return (
        <Popover modal open={abierto} onOpenChange={alCambiarApertura}>
            <input
                type="hidden"
                name={name}
                value={actual}
                disabled={disabled}
            />

            <PopoverTrigger asChild>
                <button
                    id={id}
                    type="button"
                    role="combobox"
                    aria-expanded={abierto}
                    aria-haspopup="listbox"
                    aria-invalid={invalid || undefined}
                    disabled={disabled}
                    className={cn(
                        'flex h-9 w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-1 text-left text-sm shadow-xs outline-none',
                        'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                        'disabled:cursor-not-allowed disabled:opacity-50',
                        'aria-invalid:border-destructive',
                        plano && ESTILO_CELDA,
                    )}
                >
                    <span
                        className={cn(
                            'truncate',
                            !seleccionada && 'text-muted-foreground',
                        )}
                    >
                        {seleccionada ? seleccionada.etiqueta : placeholder}
                    </span>
                    <ChevronsUpDown className="size-4 shrink-0 text-muted-foreground" />
                </button>
            </PopoverTrigger>

            <PopoverContent
                align="start"
                className="w-(--radix-popover-trigger-width) p-0"
            >
                <div className="flex items-center gap-2 border-b px-3">
                    <Search className="size-4 shrink-0 text-muted-foreground" />
                    <input
                        autoFocus
                        value={texto}
                        onChange={(evento) => {
                            setTexto(evento.target.value);
                            setResaltado(0);
                        }}
                        onKeyDown={alPulsarTecla}
                        placeholder="Buscar…"
                        aria-label="Buscar en la lista"
                        className="h-9 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    />
                </div>

                {entradas.length === 0 ? (
                    <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                        Sin resultados
                    </p>
                ) : (
                    <ul
                        ref={listaRef}
                        role="listbox"
                        className="max-h-60 overflow-y-auto p-1"
                    >
                        {entradas.map((entrada, posicion) => {
                            const elegida = String(entrada.valor) === actual;

                            return (
                                <li
                                    key={`${entrada.valor}-${posicion}`}
                                    role="option"
                                    aria-selected={elegida}
                                    onMouseEnter={() => setResaltado(posicion)}
                                    onClick={() =>
                                        elegir(String(entrada.valor))
                                    }
                                    className={cn(
                                        'flex cursor-pointer items-center justify-between gap-2 rounded-sm px-2 py-1.5 text-sm',
                                        posicion === resaltado &&
                                            'bg-accent text-accent-foreground',
                                        entrada.valor === '' &&
                                            'text-muted-foreground',
                                    )}
                                >
                                    <span className="truncate">
                                        {entrada.etiqueta}
                                    </span>
                                    {elegida && (
                                        <Check className="size-4 shrink-0" />
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                )}
            </PopoverContent>
        </Popover>
    );
}
