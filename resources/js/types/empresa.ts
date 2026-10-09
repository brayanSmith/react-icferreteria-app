export type CuentaBancaria = {
    banco: string;
    tipo: 'AHORRO' | 'CORRIENTE';
    numero: string;
    titular: string | null;
};

/** Marca de la empresa que comparte el servidor en todas las páginas. */
export type EmpresaMarca = {
    nombre: string;
    logo_url: string | null;
    icono_url: string | null;
    /** Variables CSS de color (vacío como lista cuando no hay ninguno). */
    colores: Record<string, string> | [];
};

export type Empresa = {
    nombre_empresa: string;
    nit_empresa: string | null;
    email_empresa: string | null;
    telefono_empresa: string | null;
    direccion_empresa: string | null;
    color_primario: string | null;
    color_primario_oscuro: string | null;
    color_menu: string | null;
    color_menu_oscuro: string | null;
    mostrar_productos_sin_inventario: boolean;
    cuentas_bancarias: CuentaBancaria[];
    logo_url: string | null;
    icono_url: string | null;
};
