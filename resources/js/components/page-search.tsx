import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useNavegacion } from '@/hooks/use-navegacion';
import { GRUPOS_NAVEGACION } from '@/lib/navigation';
import { normalizarTexto } from '@/lib/text';
import { cn, toUrl } from '@/lib/utils';

/**
 * Buscador de páginas: se abre con el botón de la barra superior o con
 * Ctrl+K (⌘K en Mac). Se escribe para filtrar, se mueve con las flechas y se
 * entra con Enter. Solo ofrece las páginas a las que el usuario tiene acceso.
 */
export default function PageSearch() {
    const paginas = useNavegacion();
    const [abierto, setAbierto] = useState(false);
    const [texto, setTexto] = useState('');
    const [resaltado, setResaltado] = useState(0);
    const listaRef = useRef<HTMLUListElement>(null);

    // Atajo global para abrir y cerrar el buscador desde cualquier pantalla.
    useEffect(() => {
        function alPulsarTecla(evento: globalThis.KeyboardEvent) {
            if (
                (evento.ctrlKey || evento.metaKey) &&
                evento.key.toLowerCase() === 'k'
            ) {
                evento.preventDefault();
                alternar();
            }
        }

        window.addEventListener('keydown', alPulsarTecla);

        return () => window.removeEventListener('keydown', alPulsarTecla);
    });

    function alternar() {
        cambiarApertura(!abierto);
    }

    function cambiarApertura(nuevoEstado: boolean) {
        setAbierto(nuevoEstado);

        if (nuevoEstado) {
            setTexto('');
            setResaltado(0);
        }
    }

    // Coincide con el título, el grupo o las palabras clave de cada página.
    const resultados = useMemo(() => {
        const consulta = normalizarTexto(texto.trim());

        return paginas.filter((pagina) =>
            normalizarTexto(
                [
                    pagina.title,
                    GRUPOS_NAVEGACION[pagina.grupo],
                    ...(pagina.palabrasClave ?? []),
                ].join(' '),
            ).includes(consulta),
        );
    }, [paginas, texto]);

    // Mantiene a la vista la entrada resaltada al moverse con el teclado.
    useEffect(() => {
        listaRef.current?.children[resaltado]?.scrollIntoView({
            block: 'nearest',
        });
    }, [resaltado, abierto]);

    function ir(posicion: number) {
        const pagina = resultados[posicion];

        if (!pagina) {
            return;
        }

        setAbierto(false);
        router.visit(toUrl(pagina.href));
    }

    function alPulsarTeclaEnCampo(evento: KeyboardEvent<HTMLInputElement>) {
        if (evento.key === 'ArrowDown') {
            evento.preventDefault();
            setResaltado((actual) =>
                Math.min(resultados.length - 1, actual + 1),
            );
        } else if (evento.key === 'ArrowUp') {
            evento.preventDefault();
            setResaltado((actual) => Math.max(0, actual - 1));
        } else if (evento.key === 'Enter') {
            evento.preventDefault();
            ir(resaltado);
        }
    }

    return (
        <Dialog open={abierto} onOpenChange={cambiarApertura}>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="ml-auto gap-2 text-muted-foreground"
                    aria-label="Buscar página"
                    data-test="buscar-pagina"
                >
                    <Search className="size-4" />
                    <span className="hidden sm:inline">Buscar página…</span>
                    <kbd className="pointer-events-none hidden rounded border bg-muted px-1.5 font-mono text-[10px] font-medium sm:inline">
                        Ctrl K
                    </kbd>
                </Button>
            </DialogTrigger>

            <DialogContent className="gap-0 overflow-hidden p-0 sm:max-w-lg">
                <DialogTitle className="sr-only">Buscar página</DialogTitle>
                <DialogDescription className="sr-only">
                    Escribe para filtrar las páginas y pulsa Enter para entrar.
                </DialogDescription>

                <div className="flex items-center gap-2 border-b pr-10 pl-4">
                    <Search className="size-4 shrink-0 text-muted-foreground" />
                    <input
                        autoFocus
                        value={texto}
                        onChange={(evento) => {
                            setTexto(evento.target.value);
                            setResaltado(0);
                        }}
                        onKeyDown={alPulsarTeclaEnCampo}
                        placeholder="¿A dónde quieres ir?"
                        aria-label="Buscar página"
                        className="h-12 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    />
                </div>

                {resultados.length === 0 ? (
                    <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                        No se encontraron páginas para “{texto}”.
                    </p>
                ) : (
                    <ul
                        ref={listaRef}
                        role="listbox"
                        className="max-h-80 overflow-y-auto p-2"
                    >
                        {resultados.map((pagina, posicion) => {
                            const Icono = pagina.icon;

                            return (
                                <li
                                    key={pagina.title}
                                    role="option"
                                    aria-selected={posicion === resaltado}
                                    onMouseEnter={() => setResaltado(posicion)}
                                    onClick={() => ir(posicion)}
                                    className={cn(
                                        'flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 text-sm',
                                        posicion === resaltado &&
                                            'bg-accent text-accent-foreground',
                                    )}
                                >
                                    {Icono && (
                                        <Icono className="size-4 shrink-0 text-muted-foreground" />
                                    )}
                                    <span className="flex-1 truncate font-medium">
                                        {pagina.title}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {GRUPOS_NAVEGACION[pagina.grupo]}
                                    </span>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </DialogContent>
        </Dialog>
    );
}
