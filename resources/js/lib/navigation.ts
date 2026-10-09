import {
    Building2,
    Contact,
    FlaskConical,
    Landmark,
    LayoutGrid,
    Package,
    PackagePlus,
    Palette,
    Route,
    Ruler,
    ShieldCheck,
    Tags,
    Truck,
    User,
    UserCog,
    Users,
    Warehouse,
} from 'lucide-react';
import { dashboard } from '@/routes';
import { index as tenantsIndex } from '@/routes/admin/tenants';
import { edit as appearanceEdit } from '@/routes/appearance';
import { index as bodegasIndex } from '@/routes/gestion/bodegas';
import { index as categoriasIndex } from '@/routes/gestion/categorias';
import { index as clientesIndex } from '@/routes/gestion/clientes';
import { edit as empresaEdit } from '@/routes/gestion/empresa';
import { index as formulasIndex } from '@/routes/gestion/formulas';
import { index as medidasIndex } from '@/routes/gestion/medidas';
import { index as productosIndex } from '@/routes/gestion/productos';
import { index as proveedoresIndex } from '@/routes/gestion/proveedores';
import { index as pucsIndex } from '@/routes/gestion/pucs';
import { index as rolesIndex } from '@/routes/gestion/roles';
import { index as rutasIndex } from '@/routes/gestion/rutas';
import { index as stockInicialIndex } from '@/routes/gestion/stock-inicial';
import { index as usuariosIndex } from '@/routes/gestion/usuarios';
import { edit as profileEdit } from '@/routes/profile';
import { edit as securityEdit } from '@/routes/security';
import type { NavItem } from '@/types';

/** Secciones en las que se agrupan las páginas, en el orden en que se muestran. */
export const GRUPOS_NAVEGACION = {
    principal: 'Platform',
    catalogos: 'Catálogos',
    gestion: 'Gestión',
    administracion: 'Administración',
    cuenta: 'Mi cuenta',
} as const;

export type GrupoNavegacion = keyof typeof GRUPOS_NAVEGACION;

export type ItemNavegacion = NavItem & {
    grupo: GrupoNavegacion;
    /** Permiso que habilita la página. Sin permiso, la ve cualquier usuario. */
    permiso?: string;
    /** Solo el super administrador de la plataforma la ve. */
    soloSuperAdmin?: boolean;
    /** Si es false, la página solo aparece en el buscador y no en el menú lateral. */
    enMenu: boolean;
    /** Palabras extra con las que el buscador también la encuentra. */
    palabrasClave?: string[];
};

/**
 * Todas las páginas de la aplicación a las que se llega desde el menú o el
 * buscador. El menú lateral y el buscador leen esta misma lista, así una
 * página nueva se agrega en un solo lugar.
 */
export const ITEMS_NAVEGACION: ItemNavegacion[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        grupo: 'principal',
        enMenu: true,
        palabrasClave: ['inicio', 'resumen'],
    },

    {
        title: 'Productos',
        href: productosIndex(),
        icon: Package,
        grupo: 'catalogos',
        permiso: 'productos.ver',
        enMenu: true,
        palabrasClave: ['inventario', 'articulos', 'precios'],
    },
    {
        title: 'Clientes',
        href: clientesIndex(),
        icon: Contact,
        grupo: 'catalogos',
        permiso: 'clientes.ver',
        enMenu: true,
        palabrasClave: ['compradores', 'cartera'],
    },
    {
        title: 'Fórmulas',
        href: formulasIndex(),
        icon: FlaskConical,
        grupo: 'catalogos',
        permiso: 'formulas.ver',
        enMenu: true,
        palabrasClave: ['recetas', 'ingredientes', 'produccion', 'composicion'],
    },
    {
        title: 'Stock inicial',
        href: stockInicialIndex(),
        icon: PackagePlus,
        grupo: 'catalogos',
        permiso: 'stock_inicial.ver',
        enMenu: true,
        palabrasClave: ['inventario', 'existencias', 'cantidades'],
    },
    {
        title: 'Categorías',
        href: categoriasIndex(),
        icon: Tags,
        grupo: 'catalogos',
        permiso: 'categorias.ver',
        enMenu: true,
        palabrasClave: ['subcategorias', 'clasificacion'],
    },
    {
        title: 'Medidas',
        href: medidasIndex(),
        icon: Ruler,
        grupo: 'catalogos',
        permiso: 'medidas.ver',
        enMenu: true,
        palabrasClave: ['unidades', 'kilos', 'metros'],
    },
    {
        title: 'Bodegas',
        href: bodegasIndex(),
        icon: Warehouse,
        grupo: 'catalogos',
        permiso: 'bodegas.ver',
        enMenu: true,
        palabrasClave: ['almacen', 'depositos'],
    },
    {
        title: 'Proveedores',
        href: proveedoresIndex(),
        icon: Truck,
        grupo: 'catalogos',
        permiso: 'proveedores.ver',
        enMenu: true,
        palabrasClave: ['compras', 'rut', 'nit'],
    },
    {
        title: 'Rutas',
        href: rutasIndex(),
        icon: Route,
        grupo: 'catalogos',
        permiso: 'rutas.ver',
        enMenu: true,
        palabrasClave: ['reparto', 'zonas'],
    },
    {
        title: 'Plan de cuentas',
        href: pucsIndex(),
        icon: Landmark,
        grupo: 'catalogos',
        permiso: 'pucs.ver',
        enMenu: true,
        palabrasClave: ['puc', 'contabilidad', 'cuentas'],
    },

    {
        title: 'Mi empresa',
        href: empresaEdit(),
        icon: Building2,
        grupo: 'gestion',
        permiso: 'empresa.ver',
        enMenu: true,
        palabrasClave: [
            'logo',
            'nit',
            'datos de la empresa',
            'cuentas bancarias',
        ],
    },
    {
        title: 'Usuarios',
        href: usuariosIndex(),
        icon: Users,
        grupo: 'gestion',
        permiso: 'usuarios.ver',
        enMenu: true,
        palabrasClave: ['personal', 'empleados', 'vendedores'],
    },
    {
        title: 'Roles',
        href: rolesIndex(),
        icon: ShieldCheck,
        grupo: 'gestion',
        permiso: 'roles.ver',
        enMenu: true,
        palabrasClave: ['permisos', 'accesos'],
    },

    {
        title: 'Tenants',
        href: tenantsIndex(),
        icon: Building2,
        grupo: 'administracion',
        soloSuperAdmin: true,
        enMenu: true,
        palabrasClave: ['empresas', 'plataforma', 'administradores'],
    },

    {
        title: 'Perfil',
        href: profileEdit(),
        icon: User,
        grupo: 'cuenta',
        enMenu: false,
        palabrasClave: ['cuenta', 'nombre', 'correo', 'eliminar cuenta'],
    },
    {
        title: 'Seguridad',
        href: securityEdit(),
        icon: UserCog,
        grupo: 'cuenta',
        enMenu: false,
        palabrasClave: [
            'contraseña',
            'clave',
            'passkeys',
            'dos factores',
            '2fa',
        ],
    },
    {
        title: 'Apariencia',
        href: appearanceEdit(),
        icon: Palette,
        grupo: 'cuenta',
        enMenu: false,
        palabrasClave: ['tema', 'oscuro', 'claro', 'dark', 'light'],
    },
];
