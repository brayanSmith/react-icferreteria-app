import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/tenants';
import type { TenantFilters } from '@/types';

export default function TenantFiltersBar({
    filtros,
}: {
    filtros: TenantFilters;
}) {
    const [buscar, setBuscar] = useState(filtros.buscar);
    const [eliminados, setEliminados] = useState(filtros.eliminados);

    function aplicar(nuevos: Partial<TenantFilters> = {}) {
        const datos = { buscar, eliminados, ...nuevos };

        router.get(
            index.url(),
            {
                buscar: datos.buscar || undefined,
                eliminados: datos.eliminados ? 1 : undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    function enviar(event: FormEvent) {
        event.preventDefault();
        aplicar();
    }

    return (
        <form onSubmit={enviar} className="flex flex-wrap items-center gap-3">
            <Input
                value={buscar}
                onChange={(event) => setBuscar(event.target.value)}
                placeholder="Buscar por nombre o slug"
                className="w-full sm:w-72"
                aria-label="Buscar tenants"
            />
            <Button type="submit" variant="secondary">
                Buscar
            </Button>
            <div className="flex items-center gap-2">
                <Checkbox
                    id="eliminados"
                    checked={eliminados}
                    onCheckedChange={(valor) => {
                        setEliminados(valor === true);
                        aplicar({ eliminados: valor === true });
                    }}
                />
                <Label htmlFor="eliminados">Ver eliminados</Label>
            </div>
        </form>
    );
}
