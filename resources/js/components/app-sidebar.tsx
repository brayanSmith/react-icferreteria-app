import { Link } from '@inertiajs/react';
import { BookOpen, FolderGit2 } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useNavegacion } from '@/hooks/use-navegacion';
import { GRUPOS_NAVEGACION } from '@/lib/navigation';
import type { GrupoNavegacion } from '@/lib/navigation';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

/** Secciones del menú lateral, en orden. "Mi cuenta" solo aparece en el buscador. */
const SECCIONES_DEL_MENU: GrupoNavegacion[] = [
    'principal',
    'catalogos',
    'gestion',
    'administracion',
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

/**
 * Menú lateral. Las opciones salen de la lista central de páginas
 * (`ITEMS_NAVEGACION`), filtradas por los permisos de quien está dentro; el
 * buscador de la barra superior lee esa misma lista.
 */
export function AppSidebar() {
    const paginas = useNavegacion();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {SECCIONES_DEL_MENU.map((seccion) => {
                    const items = paginas.filter(
                        (pagina) => pagina.grupo === seccion && pagina.enMenu,
                    );

                    return items.length > 0 ? (
                        <NavMain
                            key={seccion}
                            items={items}
                            label={GRUPOS_NAVEGACION[seccion]}
                        />
                    ) : null;
                })}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
