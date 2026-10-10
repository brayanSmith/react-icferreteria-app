import { Button } from '@/components/ui/button';

/**
 * Páginas que se muestran: la primera, la última y las vecinas de la actual,
 * con `null` donde se salta un tramo ("…").
 */
function paginasVisibles(actual: number, total: number): (number | null)[] {
    const paginas = new Set<number>([0, total - 1]);

    for (let pagina = actual - 1; pagina <= actual + 1; pagina++) {
        if (pagina >= 0 && pagina < total) {
            paginas.add(pagina);
        }
    }

    const ordenadas = [...paginas].sort((a, b) => a - b);
    const resultado: (number | null)[] = [];

    ordenadas.forEach((pagina, posicion) => {
        if (posicion > 0 && pagina - ordenadas[posicion - 1] > 1) {
            resultado.push(null);
        }

        resultado.push(pagina);
    });

    return resultado;
}

/**
 * Pie de los listados que se paginan en el navegador: "Mostrando X–Y de Z" y
 * los botones de página. Cambiar de página no hace ninguna petición.
 */
type PaginationLocalProps = {
    total: number;
    /** Página actual, empezando en 0. */
    pagina: number;
    porPagina: number;
    onCambiar: (pagina: number) => void;
};

export default function PaginationLocal({
    total,
    pagina,
    porPagina,
    onCambiar,
}: PaginationLocalProps) {
    const cantidadPaginas = Math.max(1, Math.ceil(total / porPagina));
    const desde = pagina * porPagina + 1;
    const hasta = Math.min(total, (pagina + 1) * porPagina);

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground">
            <span>
                {total === 0
                    ? 'Sin resultados'
                    : `Mostrando ${desde}–${hasta} de ${total}`}
            </span>
            {cantidadPaginas > 1 && (
                <nav
                    className="flex flex-wrap items-center gap-1"
                    aria-label="Paginación"
                >
                    <Button
                        size="sm"
                        variant="outline"
                        disabled={pagina === 0}
                        onClick={() => onCambiar(pagina - 1)}
                        aria-label="Página anterior"
                    >
                        ‹
                    </Button>
                    {paginasVisibles(pagina, cantidadPaginas).map(
                        (numero, posicion) =>
                            numero === null ? (
                                <span key={`salto-${posicion}`} className="px-1">
                                    …
                                </span>
                            ) : (
                                <Button
                                    key={numero}
                                    size="sm"
                                    variant={
                                        numero === pagina
                                            ? 'default'
                                            : 'outline'
                                    }
                                    onClick={() => onCambiar(numero)}
                                >
                                    {numero + 1}
                                </Button>
                            ),
                    )}
                    <Button
                        size="sm"
                        variant="outline"
                        disabled={pagina >= cantidadPaginas - 1}
                        onClick={() => onCambiar(pagina + 1)}
                        aria-label="Página siguiente"
                    >
                        ›
                    </Button>
                </nav>
            )}
        </div>
    );
}
