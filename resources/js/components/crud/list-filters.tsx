import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ListFiltersProps = {
    /** URL del listado (por ejemplo `index.url()` de Wayfinder). */
    url: string;
    /** Texto buscado actualmente. Sin él (listados cortos) no se muestra el buscador. */
    buscar?: string;
    placeholder?: string;
    /** Si se indica, muestra el casillero para ver solo los registros eliminados. */
    /** Otros parámetros del listado (por ejemplo la pestaña activa) que se conservan al buscar. */
    parametrosExtra?: Record<string, string>;
    eliminados?: {
        /** Nombre del parámetro en la URL (`eliminados` o `eliminadas`). */
        parametro: string;
        etiqueta: string;
        activo: boolean;
    };
};

/**
 * Barra de búsqueda y filtro de eliminados de los listados. Cambia la URL
 * (con `buscar` y, si aplica, el parámetro de eliminados) y Inertia recarga
 * solo los datos, sin repintar toda la página.
 */
export default function ListFilters({
    url,
    buscar,
    placeholder,
    parametrosExtra = {},
    eliminados,
}: ListFiltersProps) {
    const [texto, setTexto] = useState(buscar ?? '');
    const conBuscador = buscar !== undefined;
    const [verEliminados, setVerEliminados] = useState(
        eliminados?.activo ?? false,
    );

    function aplicar(nuevoTexto: string, nuevoEliminados: boolean) {
        const parametros: Record<string, string | number> = {
            ...parametrosExtra,
        };

        if (nuevoTexto !== '') {
            parametros.buscar = nuevoTexto;
        }

        if (eliminados && nuevoEliminados) {
            parametros[eliminados.parametro] = 1;
        }

        router.get(url, parametros, { preserveState: true, replace: true });
    }

    function enviar(evento: FormEvent) {
        evento.preventDefault();
        aplicar(texto, verEliminados);
    }

    return (
        <form onSubmit={enviar} className="flex flex-wrap items-center gap-3">
            {conBuscador && (
                <>
                    <Input
                        value={texto}
                        onChange={(evento) => setTexto(evento.target.value)}
                        placeholder={placeholder}
                        className="w-full sm:w-72"
                        aria-label={placeholder}
                    />
                    <Button type="submit" variant="secondary">
                        Buscar
                    </Button>
                </>
            )}
            {eliminados && (
                <div className="flex items-center gap-2">
                    <Checkbox
                        id={`filtro-${eliminados.parametro}`}
                        checked={verEliminados}
                        onCheckedChange={(valor) => {
                            setVerEliminados(valor === true);
                            aplicar(texto, valor === true);
                        }}
                    />
                    <Label htmlFor={`filtro-${eliminados.parametro}`}>
                        {eliminados.etiqueta}
                    </Label>
                </div>
            )}
        </form>
    );
}
