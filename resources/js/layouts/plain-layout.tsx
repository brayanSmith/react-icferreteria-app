import type { ReactNode } from 'react';
import { useEmpresaBranding } from '@/hooks/use-empresa-branding';

/**
 * Diseño sin marcos para páginas públicas (bienvenida). Solo aplica la marca
 * del título para que no quede la de una página anterior.
 */
export default function PlainLayout({ children }: { children: ReactNode }) {
    useEmpresaBranding();

    return <>{children}</>;
}
