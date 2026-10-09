import PaginationLinks from '@/components/pagination-links';
import type { Paginated } from '@/types';

/**
 * Pie de los listados paginados: "Mostrando X–Y de Z" y los botones de página.
 */
export default function PaginationFooter<T>({
    datos,
}: {
    datos: Paginated<T>;
}) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground">
            <span>
                {datos.meta.total === 0
                    ? 'Sin resultados'
                    : `Mostrando ${datos.meta.from}–${datos.meta.to} de ${datos.meta.total}`}
            </span>
            <PaginationLinks links={datos.meta.links} />
        </div>
    );
}
