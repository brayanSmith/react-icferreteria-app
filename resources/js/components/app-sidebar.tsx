import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    FolderGit2,
    Landmark,
    LayoutGrid,
    Package,
    PackagePlus,
    Route,
    Ruler,
    ShieldCheck,
    Tags,
    Truck,
    Users,
    Warehouse,
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
import { index as bodegasIndex } from '@/routes/gestion/bodegas';
import { index as categoriasIndex } from '@/routes/gestion/categorias';
import { edit as empresaEdit } from '@/routes/gestion/empresa';
import { index as medidasIndex } from '@/routes/gestion/medidas';
import { index as productosIndex } from '@/routes/gestion/productos';
import { index as proveedoresIndex } from '@/routes/gestion/proveedores';
import { index as pucsIndex } from '@/routes/gestion/pucs';
import { index as rolesIndex } from '@/routes/gestion/roles';
import { index as rutasIndex } from '@/routes/gestion/rutas';
import { index as stockInicialIndex } from '@/routes/gestion/stock-inicial';
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

// Catálogos del tenant (productos y datos maestros) y el permiso que habilita cada uno.
const catalogoNavItems: (NavItem & { permiso: string })[] = [
    {
        title: 'Productos',
        href: productosIndex(),
        icon: Package,
        permiso: 'productos.ver',
    },
    {
        title: 'Stock inicial',
        href: stockInicialIndex(),
        icon: PackagePlus,
        permiso: 'stock_inicial.ver',
    },
    {
        title: 'Categorías',
        href: categoriasIndex(),
        icon: Tags,
        permiso: 'categorias.ver',
    },
    {
        title: 'Medidas',
        href: medidasIndex(),
        icon: Ruler,
        permiso: 'medidas.ver',
    },
    {
        title: 'Bodegas',
        href: bodegasIndex(),
        icon: Warehouse,
        permiso: 'bodegas.ver',
    },
    {
        title: 'Proveedores',
        href: proveedoresIndex(),
        icon: Truck,
        permiso: 'proveedores.ver',
    },
    { title: 'Rutas', href: rutasIndex(), icon: Route, permiso: 'rutas.ver' },
    {
        title: 'Plan de cuentas',
        href: pucsIndex(),
        icon: Landmark,
        permiso: 'pucs.ver',
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
    const visibles = (items: (NavItem & { permiso: string })[]) =>
        auth.user.tenant_id == null
            ? []
            : items.filter((item) => auth.permisos.includes(item.permiso));
    const opcionesGestion = visibles(gestionNavItems);
    const opcionesCatalogo = visibles(catalogoNavItems);

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
                {opcionesCatalogo.length > 0 && (
                    <NavMain items={opcionesCatalogo} label="Catálogos" />
                )}
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
