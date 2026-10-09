import { usePage } from '@inertiajs/react';
import type { Auth } from '@/types';

/**
 * Indica si el usuario tiene un permiso, por ejemplo `can('roles.crear')`.
 * Solo sirve para mostrar u ocultar botones: el servidor es quien autoriza.
 */
export function useCan(): (permiso: string) => boolean {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (permiso) => auth.permisos.includes(permiso);
}
