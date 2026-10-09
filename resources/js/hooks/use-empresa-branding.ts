import { usePage } from '@inertiajs/react';
import { useLayoutEffect } from 'react';
import { setNombreMarca } from '@/lib/brand';
import type { EmpresaMarca } from '@/types';

/**
 * Aplica la marca de la empresa del usuario: el nombre en el título de la
 * pestaña y el ícono como favicon. Se monta en los diseños de página, así que
 * reacciona cuando la empresa cambia sin recargar la página.
 *
 * Los diseños sin sesión (login, registro) también lo llaman: sin empresa
 * restablece el nombre de la aplicación, y así el renderizado en servidor
 * nunca reutiliza la marca de otra petición.
 */
export function useEmpresaBranding(): void {
    const { marca } = usePage<{ marca: EmpresaMarca | null }>().props;
    const iconoUrl = marca?.icono_url ?? null;

    // Durante el render (y no en un efecto) para que también valga en SSR y
    // esté lista antes de que Inertia recalcule el título.
    setNombreMarca(marca?.nombre);

    useLayoutEffect(() => {
        const enlaces =
            document.querySelectorAll<HTMLLinkElement>('link[rel~="icon"]');

        enlaces.forEach((enlace) => {
            // Se guarda el valor original para poder volver al predeterminado.
            enlace.dataset.hrefOriginal ??= enlace.getAttribute('href') ?? '';
            enlace.dataset.typeOriginal ??= enlace.getAttribute('type') ?? '';

            if (iconoUrl) {
                enlace.setAttribute('href', iconoUrl);
                enlace.removeAttribute('type');

                return;
            }

            enlace.setAttribute('href', enlace.dataset.hrefOriginal);

            if (enlace.dataset.typeOriginal) {
                enlace.setAttribute('type', enlace.dataset.typeOriginal);
            }
        });
    }, [iconoUrl]);
}
