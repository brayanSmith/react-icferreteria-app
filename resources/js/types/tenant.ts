export type Tenant = {
    id: number;
    nombre: string;
    slug: string;
    activo: boolean;
    users_count?: number;
    deleted_at: string | null;
    created_at: string | null;
};

export type TenantFilters = {
    buscar: string;
    eliminados: boolean;
};

export type TenantAdmin = {
    id: number;
    name: string;
    email: string;
    n_documento: string;
    debe_cambiar_password: boolean;
};

/** Datos que se capturan para un administrador. */
export type TenantAdminValores = {
    name: string;
    email: string;
    n_documento: string;
    password: string;
};

/** Administrador aún sin guardar, armado en pantalla al crear un tenant. */
export type TenantAdminBorrador = TenantAdminValores & {
    /** Identificador local estable para la lista; no viaja al servidor. */
    clave: number;
};
