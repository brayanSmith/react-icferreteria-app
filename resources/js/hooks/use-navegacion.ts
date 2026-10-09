import { usePage } from '@inertiajs/react';
import { ITEMS_NAVEGACION } from '@/lib/navigation';
import type { ItemNavegacion } from '@/lib/navigation';
import type { Auth } from '@/types';

/**
 * Páginas a las que el usuario actual puede entrar, según sus permisos.
 *
 * Es solo para mostrar u ocultar opciones; el servidor es quien autoriza cada
 * petición. El super administrador no tiene tenant, así que no ve las páginas
 * de gestión interna.
 */
export function useNavegacion(): ItemNavegacion[] {
    const { auth } = usePage<{ auth: Auth }>().props;
    const tieneTenant = auth.user.tenant_id != null;

    return ITEMS_NAVEGACION.filter((item) => {
        if (item.soloSuperAdmin) {
            return Boolean(auth.user.es_super_admin);
        }

        if (item.permiso) {
            return tieneTenant && auth.permisos.includes(item.permiso);
        }

        return true;
    });
}
