import {
    flexRender,
    getCoreRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';
import type {
    ColumnDef,
    RowData,
    SortingState,
} from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import PaginationLocal from '@/components/crud/pagination-local';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

// Dato extra que guardamos en cada columna de TanStack: hacia dónde se alinea.
declare module '@tanstack/react-table' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface ColumnMeta<TData extends RowData, TValue> {
        alinear?: 'izquierda' | 'derecha';
    }
}

/** Función que da el valor filtrable de una columna, si lo tiene. */
function valorDeFiltro<T>(columna: ColumnaCrud<T>) {
    return columna.valorFiltro ?? columna.valorOrden;
}

/**
 * Control de filtro bajo el encabezado de una columna: un campo de texto o, con
 * `filtro: 'lista'`, un desplegable con los valores distintos de la columna.
 */
function FiltroDeColumna<T>({
    columna,
    filas,
    valor,
    onCambiar,
}: {
    columna: ColumnaCrud<T>;
    filas: T[];
    valor: string;
    onCambiar: (valor: string) => void;
}) {
    const obtener = valorDeFiltro(columna);
    const opciones = useMemo(() => {
        if (columna.filtro !== 'lista' || !obtener) {
            return [];
        }

        return [...new Set(filas.map((fila) => String(obtener(fila) ?? '')))]
            .filter((opcion) => opcion !== '')
            .sort((a, b) => a.localeCompare(b, 'es'));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [filas, columna.filtro]);
    const etiqueta = `Filtrar `;

    if (columna.filtro === 'lista') {
        return (
            <select
                value={valor}
                onChange={(evento) => onCambiar(evento.target.value)}
                aria-label={etiqueta}
                className="h-8 w-full rounded-md border bg-background px-2 text-sm font-normal"
            >
                <option value="">Todos</option>
                {opciones.map((opcion) => (
                    <option key={opcion} value={opcion}>
                        {opcion}
                    </option>
                ))}
            </select>
        );
    }

    return (
        <Input
            type="search"
            value={valor}
            onChange={(evento) => onCambiar(evento.target.value)}
            placeholder="Filtrar"
            aria-label={etiqueta}
            className="h-8 min-w-24 px-2 text-sm font-normal"
        />
    );
}

/** Minúsculas y sin tildes, para que "camion" encuentre "Camión". */
function normalizar(texto: string): string {
    return texto
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase();
}

export type ColumnaCrud<T> = {
    encabezado: string;
    /** Qué se muestra en la celda de cada fila. */
    celda: (fila: T) => ReactNode;
    alinear?: 'izquierda' | 'derecha';
    /**
     * Valor por el que se ordena la columna. Si se indica, el encabezado se
     * vuelve clicable. Se ordena en el navegador, sobre todas las filas.
     */
    valorOrden?: (fila: T) => string | number | null;
    /**
     * Texto por el que se filtra la columna. Si no se indica se usa `valorOrden`;
     * sin ninguno de los dos, la columna no se puede filtrar.
     */
    valorFiltro?: (fila: T) => string | number | null;
    /**
     * `texto` (por defecto) escribe lo que debe contener la celda; `lista` ofrece
     * los valores distintos de la columna, útil para estados y tipos.
     */
    filtro?: 'texto' | 'lista';
};

type CrudTableProps<T> = {
    columnas: ColumnaCrud<T>[];
    filas: T[];
    /** Identificador único de la fila, para las claves de React. */
    clave: (fila: T) => string | number;
    /** Botones de cada fila (editar, eliminar...). Si no hay, no se pinta la columna. */
    acciones?: (fila: T) => ReactNode;
    /** Mensaje cuando no hay filas. */
    vacio: string;
    /**
     * Si se indica, muestra un buscador que filtra en el navegador. `texto`
     * devuelve todo lo que se puede buscar de la fila; se ignoran mayúsculas y
     * tildes, y cada palabra escrita debe aparecer.
     */
    busqueda?: {
        placeholder: string;
        texto: (fila: T) => string;
    };
    /** Controles extra junto al buscador (por ejemplo, "Ver eliminados"). */
    barra?: ReactNode;
    /** Filas por página. */
    porPagina?: number;
    /** En `false` se muestran todas las filas y sin pie (listados que ya pagina el servidor). */
    paginar?: boolean;
};

/**
 * Tabla genérica de los listados, construida sobre TanStack Table.
 *
 * Recibe todas las filas del listado y las columnas declarativas
 * (`ColumnaCrud`); TanStack se encarga de buscar, ordenar y paginar en el
 * navegador, así que ninguna de esas acciones pide datos al servidor. Este
 * componente aporta el estilo, el buscador, el estado vacío y la columna de
 * acciones.
 *
 * `filas` debe mantener la misma referencia mientras no cambien los datos
 * (usa `useMemo` si las filtras), porque un arreglo nuevo vuelve a la
 * primera página.
 */
export default function CrudTable<T>({
    columnas,
    filas,
    clave,
    acciones,
    vacio,
    busqueda,
    barra,
    porPagina = 15,
    paginar = true,
}: CrudTableProps<T>) {
    const [orden, setOrden] = useState<SortingState>([]);
    const [texto, setTexto] = useState('');
    const [filtrosColumna, setFiltrosColumna] = useState<Record<string, string>>({});
    const [pagina, setPagina] = useState(0);

    // Columnas de datos, más una de acciones al final si se pidió.
    const definiciones: ColumnDef<T>[] = [
        ...columnas.map((columna): ColumnDef<T> => ({
            id: columna.encabezado,
            header: columna.encabezado,
            accessorFn: (fila) => columna.valorOrden?.(fila) ?? null,
            cell: ({ row }) => columna.celda(row.original),
            enableSorting: columna.valorOrden !== undefined,
            meta: { alinear: columna.alinear },
        })),
        ...(acciones
            ? [
                  {
                      id: 'acciones',
                      header: 'Acciones',
                      enableSorting: false,
                      meta: { alinear: 'derecha' as const },
                      cell: ({ row }) => (
                          <div className="flex justify-end gap-2">
                              {acciones(row.original)}
                          </div>
                      ),
                  } satisfies ColumnDef<T>,
              ]
            : []),
    ];

    // La búsqueda se aplica aquí, antes de la tabla, para que el total y las páginas
    // salgan siempre de las mismas filas que se muestran.
    const filasFiltradas = useMemo(() => {
        const palabras = normalizar(texto).split(/\s+/).filter(Boolean);

        const buscando = busqueda !== undefined && palabras.length > 0;
        const activos = columnas.filter(
            (columna) => (filtrosColumna[columna.encabezado] ?? '') !== '',
        );

        if (!buscando && activos.length === 0) {
            return filas;
        }

        return filas.filter((fila) => {
            if (buscando) {
                const contenido = normalizar(busqueda.texto(fila));

                if (!palabras.every((palabra) => contenido.includes(palabra))) {
                    return false;
                }
            }

            return activos.every((columna) => {
                const buscado = filtrosColumna[columna.encabezado];
                const valor = String(valorDeFiltro(columna)?.(fila) ?? '');

                return columna.filtro === 'lista'
                    ? valor === buscado
                    : normalizar(valor).includes(normalizar(buscado));
            });
        });
        // `busqueda` se recrea en cada render de la página; solo importa si existe.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [filas, texto, filtrosColumna, busqueda !== undefined]);

    const tabla = useReactTable({
        data: filasFiltradas,
        columns: definiciones,
        state: { sorting: orden },
        onSortingChange: setOrden,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getRowId: (fila) => String(clave(fila)),
    });

    const filasOrdenadas = tabla.getRowModel().rows;
    const total = filasOrdenadas.length;
    const tamanoPagina = paginar ? porPagina : Math.max(total, 1);
    const cantidadPaginas = Math.max(1, Math.ceil(total / tamanoPagina));

    // La página nunca pasa de la última, aunque se reduzcan las filas.
    const paginaActual = Math.min(pagina, cantidadPaginas - 1);
    const filasVisibles = filasOrdenadas.slice(
        paginaActual * tamanoPagina,
        (paginaActual + 1) * tamanoPagina,
    );

    // Si cambia la cantidad de filas (pestaña, eliminados) se vuelve a la primera página.
    useEffect(() => {
        setPagina(0);
    }, [total]);

    return (
        <div className="space-y-4">
            {(busqueda || barra) && (
                <div className="flex flex-wrap items-center gap-3">
                    {busqueda && (
                        <Input
                            type="search"
                            value={texto}
                            onChange={(evento) => {
                                setTexto(evento.target.value);
                                setPagina(0);
                            }}
                            placeholder={busqueda.placeholder}
                            aria-label={busqueda.placeholder}
                            className="w-full sm:w-96"
                        />
                    )}
                    {barra}
                </div>
            )}

            {filas.length === 0 ? (
                <p className="rounded-lg border p-8 text-center text-sm text-muted-foreground">
                    {vacio}
                </p>
            ) : (
                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            {tabla.getHeaderGroups().map((grupo) => (
                                <tr key={grupo.id}>
                                    {grupo.headers.map((encabezado) => {
                                        const columna = encabezado.column;
                                        const derecha =
                                            columna.columnDef.meta?.alinear ===
                                            'derecha';
                                        const sentido = columna.getIsSorted();
                                        const contenido = flexRender(
                                            columna.columnDef.header,
                                            encabezado.getContext(),
                                        );

                                        return (
                                            <th
                                                key={encabezado.id}
                                                className={cn(
                                                    'px-4 py-3 font-medium',
                                                    derecha
                                                        ? 'text-right'
                                                        : 'text-left',
                                                )}
                                                aria-sort={
                                                    sentido === 'asc'
                                                        ? 'ascending'
                                                        : sentido === 'desc'
                                                          ? 'descending'
                                                          : undefined
                                                }
                                            >
                                                {columna.getCanSort() ? (
                                                    <button
                                                        type="button"
                                                        onClick={columna.getToggleSortingHandler()}
                                                        className={cn(
                                                            'inline-flex items-center gap-1 hover:text-foreground',
                                                            derecha &&
                                                                'flex-row-reverse',
                                                        )}
                                                    >
                                                        {contenido}
                                                        {sentido === 'asc' ? (
                                                            <ArrowUp className="size-3.5" />
                                                        ) : sentido ===
                                                          'desc' ? (
                                                            <ArrowDown className="size-3.5" />
                                                        ) : (
                                                            <ChevronsUpDown className="size-3.5 text-muted-foreground" />
                                                        )}
                                                    </button>
                                                ) : (
                                                    contenido
                                                )}
                                            </th>
                                        );
                                    })}
                                </tr>
                            ))}
                            {columnas.some((columna) => valorDeFiltro(columna)) && (
                                <tr className="border-t">
                                    {columnas.map((columna) => (
                                        <th
                                            key={columna.encabezado}
                                            className="px-4 py-2 font-normal"
                                        >
                                            {valorDeFiltro(columna) && (
                                                <FiltroDeColumna
                                                    columna={columna}
                                                    filas={filas}
                                                    valor={
                                                        filtrosColumna[
                                                            columna.encabezado
                                                        ] ?? ''
                                                    }
                                                    onCambiar={(nuevo) => {
                                                        setFiltrosColumna(
                                                            (actual) => ({
                                                                ...actual,
                                                                [columna.encabezado]:
                                                                    nuevo,
                                                            }),
                                                        );
                                                    }}
                                                />
                                            )}
                                        </th>
                                    ))}
                                    {acciones && <th />}
                                </tr>
                            )}
                        </thead>
                        <tbody className="divide-y">
                            {filasVisibles.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={columnas.length + (acciones ? 1 : 0)}
                                        className="p-8 text-center text-muted-foreground"
                                    >
                                        {vacio}
                                    </td>
                                </tr>
                            )}
                            {filasVisibles.map((fila) => (
                                <tr key={fila.id}>
                                    {fila.getVisibleCells().map((celda) => (
                                        <td
                                            key={celda.id}
                                            className={cn(
                                                'px-4 py-3',
                                                celda.column.columnDef.meta
                                                    ?.alinear === 'derecha'
                                                    ? 'text-right'
                                                    : 'text-left',
                                            )}
                                        >
                                            {flexRender(
                                                celda.column.columnDef.cell,
                                                celda.getContext(),
                                            )}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {paginar && <PaginationLocal
                    total={total}
                    pagina={paginaActual}
                    porPagina={tamanoPagina}
                    onCambiar={setPagina}
                />}
        </div>
    );
}
