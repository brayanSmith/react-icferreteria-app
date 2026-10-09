import { router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type PestanaFiltro = {
    /** Valor que se envía en la URL; vacío ('') es la pestaña "Todos". */
    valor: string;
    etiqueta: string;
    /** Cantidad de registros de la pestaña (opcional). */
    cantidad?: number;
};

type FilterTabsProps = {
    /** URL del listado (por ejemplo `index.url()` de Wayfinder). */
    url: string;
    /** Nombre del parámetro de la URL que filtra (por ejemplo `tipo`). */
    parametro: string;
    pestanas: PestanaFiltro[];
    /** Valor de la pestaña activa ('' si ninguna filtra). */
    activa: string;
    /** Resto de parámetros del listado (búsqueda, eliminados...) que se conservan al cambiar de pestaña. */
    parametrosActuales?: Record<
        string,
        string | number | boolean | null | undefined
    >;
};

/**
 * Pestañas que filtran un listado por un valor. Como los listados los pagina
 * el servidor, cambiar de pestaña vuelve a pedir los datos con el parámetro
 * en la URL (y conserva la búsqueda), en lugar de filtrar en el navegador.
 */
export default function FilterTabs({
    url,
    parametro,
    pestanas,
    activa,
    parametrosActuales = {},
}: FilterTabsProps) {
    function elegir(valor: string) {
        const parametros: Record<string, string | number> = {};

        for (const [clave, contenido] of Object.entries(parametrosActuales)) {
            if (
                clave !== parametro &&
                contenido !== null &&
                contenido !== undefined &&
                contenido !== '' &&
                contenido !== false
            ) {
                parametros[clave] = contenido === true ? 1 : contenido;
            }
        }

        if (valor !== '') {
            parametros[parametro] = valor;
        }

        router.get(url, parametros, { preserveState: true, replace: true });
    }

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
                        onClick={() => elegir(pestana.valor)}
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
