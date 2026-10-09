export type Rol = {
    id: number;
    name: string;
    permisos: string[];
    users_count?: number;
};

export type RolOpcion = {
    id: number;
    name: string;
};

export type CatalogoModulo = {
    modulo: string;
    etiqueta: string;
    acciones: { permiso: string; etiqueta: string }[];
};

export type UsuarioTenant = {
    id: number;
    name: string;
    email: string;
    n_documento: string;
    cod_vendedor: string | null;
    activo: boolean;
    debe_cambiar_password: boolean;
    roles: RolOpcion[];
};
