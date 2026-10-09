import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import type { EmpresaMarca } from '@/types';

/**
 * Marca del encabezado: logo y nombre de la empresa del usuario. Mientras no
 * haya empresa (o logo) se usa el ícono y el nombre de la aplicación.
 */
export default function AppLogo() {
    const { name, marca } = usePage<{
        name: string;
        marca: EmpresaMarca | null;
    }>().props;

    return (
        <>
            {marca?.logo_url ? (
                <img
                    src={marca.logo_url}
                    alt={`Logo de ${marca.nombre}`}
                    className="aspect-square size-8 shrink-0 rounded-md object-contain"
                />
            ) : (
                <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                    <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
                </div>
            )}
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {marca?.nombre ?? name}
                </span>
            </div>
        </>
    );
}
