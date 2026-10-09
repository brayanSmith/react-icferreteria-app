import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    FolderGit2,
    LayoutGrid,
    ShieldCheck,
    Users,
} from 'lucide-react';
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
import { dashboard } from '@/routes';
import { index as tenantsIndex } from '@/routes/admin/tenants';
import { edit as empresaEdit } from '@/routes/gestion/empresa';
import { index as rolesIndex } from '@/routes/gestion/roles';
import { index as usuariosIndex } from '@/routes/gestion/usuarios';
import type { Auth, NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Tenants',
        href: tenantsIndex(),
        icon: Building2,
    },
];

// Opciones de gestión del tenant y el permiso que habilita cada una.
const gestionNavItems: (NavItem & { permiso: string })[] = [
    {
        title: 'Mi empresa',
        href: empresaEdit(),
        icon: Building2,
        permiso: 'empresa.ver',
    },
    {
        title: 'Usuarios',
        href: usuariosIndex(),
        icon: Users,
        permiso: 'usuarios.ver',
    },
    {
        title: 'Roles',
        href: rolesIndex(),
        icon: ShieldCheck,
        permiso: 'roles.ver',
    },
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

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    // El super administrador no tiene tenant, así que no ve la gestión interna.
    const opcionesGestion =
        auth.user.tenant_id == null
            ? []
            : gestionNavItems.filter((item) =>
                  auth.permisos.includes(item.permiso),
              );

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
                <NavMain items={mainNavItems} />
                {opcionesGestion.length > 0 && (
                    <NavMain items={opcionesGestion} label="Gestión" />
                )}
                {auth.user.es_super_admin && (
                    <NavMain items={adminNavItems} label="Administración" />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
