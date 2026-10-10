import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type PestanaFiltro = {
    /** Valor de la pestaña; vacío ('') es la pestaña "Todos". */
    valor: string;
    etiqueta: string;
    /** Cantidad de registros de la pestaña (opcional). */
    cantidad?: number;
};

type FilterTabsProps = {
    pestanas: PestanaFiltro[];
    /** Valor de la pestaña activa ('' si ninguna filtra). */
    activa: string;
    onCambiar: (valor: string) => void;
};

/**
 * Pestañas que filtran un listado por un valor. El filtrado ocurre en el
 * navegador: la página guarda la pestaña activa y filtra las filas que ya
 * tiene, sin pedir nada al servidor.
 */
export default function FilterTabs({
    pestanas,
    activa,
    onCambiar,
}: FilterTabsProps) {
    return (
        <div
            role="tablist"
            className="flex w-full gap-1 overflow-x-auto rounded-lg bg-muted p-1"
        >
            {pestanas.map((pestana) => {
                const seleccionada = pestana.valor === activa;

                return (
                    <button
                        key={pestana.valor || 'todos'}
                        type="button"
                        role="tab"
                        aria-selected={seleccionada}
                        onClick={() => onCambiar(pestana.valor)}
                        className={cn(
                            'flex shrink-0 items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                            seleccionada
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {pestana.etiqueta}
                        {pestana.cantidad !== undefined && (
                            <Badge
                                variant={seleccionada ? 'default' : 'secondary'}
                            >
                                {pestana.cantidad}
                            </Badge>
                        )}
                    </button>
                );
            })}
        </div>
    );
}
