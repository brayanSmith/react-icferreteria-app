import {
    flexRender,
    getCoreRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';
import type { ColumnDef, RowData, SortingState } from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

// Dato extra que guardamos en cada columna de TanStack: hacia dónde se alinea.
declare module '@tanstack/react-table' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface ColumnMeta<TData extends RowData, TValue> {
        alinear?: 'izquierda' | 'derecha';
    }
}

export type ColumnaCrud<T> = {
    encabezado: string;
    /** Qué se muestra en la celda de cada fila. */
    celda: (fila: T) => ReactNode;
    alinear?: 'izquierda' | 'derecha';
    /**
     * Valor por el que se ordena la columna. Si se indica, el encabezado se
     * vuelve clicable. Ordena solo las filas que ya están cargadas, así que
     * úsalo únicamente en listados completos (no paginados por el servidor).
     */
    valorOrden?: (fila: T) => string | number | null;
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
};

/**
 * Tabla genérica de los listados, construida sobre TanStack Table.
 *
 * Recibe columnas declarativas (`ColumnaCrud`) y las traduce a definiciones de
 * TanStack, que se encarga del modelo de filas y de la ordenación; este
 * componente solo aporta el estilo, el estado vacío y la columna de acciones.
 */
export default function CrudTable<T>({
    columnas,
    filas,
    clave,
    acciones,
    vacio,
}: CrudTableProps<T>) {
    const [orden, setOrden] = useState<SortingState>([]);

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

    const tabla = useReactTable({
        data: filas,
        columns: definiciones,
        state: { sorting: orden },
        onSortingChange: setOrden,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getRowId: (fila) => String(clave(fila)),
    });

    if (filas.length === 0) {
        return (
            <p className="rounded-lg border p-8 text-center text-sm text-muted-foreground">
                {vacio}
            </p>
        );
    }

    return (
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
                                                ) : sentido === 'desc' ? (
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
                </thead>
                <tbody className="divide-y">
                    {tabla.getRowModel().rows.map((fila) => (
                        <tr key={fila.id}>
                            {fila.getVisibleCells().map((celda) => (
                                <td
                                    key={celda.id}
                                    className={cn(
                                        'px-4 py-3',
                                        celda.column.columnDef.meta?.alinear ===
                                            'derecha'
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
    );
}
