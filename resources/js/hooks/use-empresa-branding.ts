import { usePage } from '@inertiajs/react';
import { useLayoutEffect } from 'react';
import { setNombreMarca } from '@/lib/brand';
import type { EmpresaMarca } from '@/types';

/** Variables CSS de color que comparte el servidor (ver Empresa::variablesDeColor). */
const VARIABLES_DE_COLOR = [
    '--marca',
    '--marca-oscuro',
    '--menu',
    '--menu-oscuro',
];

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
    // Se serializa para que el efecto solo corra cuando cambian los colores.
    const colores = JSON.stringify(marca?.colores ?? {});

    // Los colores de la empresa se exponen como variables y atributos de la
    // raíz; app.css los usa para teñir los controles y el menú.
    useLayoutEffect(() => {
        const raiz = document.documentElement;
        const aplicados: Record<string, string> = JSON.parse(colores);

        VARIABLES_DE_COLOR.forEach((variable) => {
            const valor = aplicados[variable];
            const atributo = `data-${variable.slice(2)}`;

            [variable, `${variable}-foreground`].forEach((nombre) => {
                if (aplicados[nombre]) {
                    raiz.style.setProperty(nombre, aplicados[nombre]);
                } else {
                    raiz.style.removeProperty(nombre);
                }
            });

            if (valor) {
                raiz.setAttribute(atributo, '');
            } else {
                raiz.removeAttribute(atributo);
            }
        });
    }, [colores]);

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
