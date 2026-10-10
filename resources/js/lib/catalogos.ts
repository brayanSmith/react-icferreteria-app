import type { OpcionDeSelect } from '@/components/crud/select-field';

/**
 * Opciones fijas de los selects de los catálogos. Reflejan los valores que
 * acepta el servidor (ver las constantes de las clases `Guardar*Request`).
 */

export const TIPOS_MEDIDA: OpcionDeSelect[] = [
    { valor: 'CANTIDAD', etiqueta: 'Cantidad' },
    { valor: 'PESO', etiqueta: 'Peso' },
    { valor: 'LONGITUD', etiqueta: 'Longitud' },
    { valor: 'VOLUMEN', etiqueta: 'Volumen' },
];

/** Nombre de cada clase del plan único de cuentas colombiano, por su número. */
export const CLASES_PUC: Record<string, string> = {
    '1': 'Activo',
    '2': 'Pasivo',
    '3': 'Patrimonio',
    '4': 'Ingresos',
    '5': 'Gastos',
    '6': 'Costos de ventas',
    '7': 'Costos de producción',
    '8': 'Cuentas de orden deudoras',
    '9': 'Cuentas de orden acreedoras',
};

/** Opciones del select de clase: "1 · Activo", "2 · Pasivo"... */
export const TIPOS_PUC: OpcionDeSelect[] = Object.entries(CLASES_PUC).map(
    ([numero, nombre]) => ({
        valor: numero,
        etiqueta: `${numero} · ${nombre}`,
    }),
);

export const TIPOS_PROVEEDOR: OpcionDeSelect[] = [
    { valor: 'REMISIONADO', etiqueta: 'Remisionado' },
    { valor: 'ELECTRONICO', etiqueta: 'Electrónico' },
];

export const CATEGORIAS_PROVEEDOR: OpcionDeSelect[] = [
    { valor: 'NO_DECLARANTE', etiqueta: 'No declarante' },
    { valor: 'DECLARANTE', etiqueta: 'Declarante' },
    { valor: 'RETENEDOR', etiqueta: 'Retenedor' },
];

export const TIPOS_CUENTA: OpcionDeSelect[] = [
    { valor: 'AHORRO', etiqueta: 'Ahorros' },
    { valor: 'CORRIENTE', etiqueta: 'Corriente' },
];

export const CATEGORIAS_PRODUCTO: OpcionDeSelect[] = [
    { valor: 'PRODUCTO_TERMINADO', etiqueta: 'Producto terminado' },
    { valor: 'MATERIA_PRIMA', etiqueta: 'Materia prima' },
    { valor: 'OTRO', etiqueta: 'Otro' },
    { valor: 'DESCUENTO', etiqueta: 'Descuento' },
    { valor: 'GASTOS', etiqueta: 'Gastos' },
];

export const VOLUMENES_PRODUCTO: OpcionDeSelect[] = [
    { valor: 'EXTRA_PEQUEÑO', etiqueta: 'Extra pequeño' },
    { valor: 'PEQUEÑO', etiqueta: 'Pequeño' },
    { valor: 'MEDIANO', etiqueta: 'Mediano' },
    { valor: 'GRANDE', etiqueta: 'Grande' },
    { valor: 'EXTRA_GRANDE', etiqueta: 'Extra grande' },
];

/** Origen de la compra del producto: nacional o importado. */
export const TIPOS_COMPRA: OpcionDeSelect[] = [
    { valor: 'NACIONAL', etiqueta: 'Nacional' },
    { valor: 'IMPORTADO', etiqueta: 'Importado' },
];

/** Tipos de movimiento de inventario. */
export const TIPOS_MOVIMIENTO_STOCK: OpcionDeSelect[] = [
    { valor: 'INGRESO', etiqueta: 'Ingreso (entra mercancía)' },
    { valor: 'SALIDA', etiqueta: 'Salida (sale mercancía)' },
];

/** Devuelve la etiqueta legible de un valor, o el valor mismo si no se conoce. */
export function etiquetaDe(
    opciones: OpcionDeSelect[],
    valor: string | null | undefined,
): string {
    if (!valor) {
        return '—';
    }

    return (
        opciones.find((opcion) => String(opcion.valor) === valor)?.etiqueta ??
        valor
    );
}

export const TIPOS_DOCUMENTO: OpcionDeSelect[] = [
    { valor: 'NIT', etiqueta: 'NIT' },
    { valor: 'CC', etiqueta: 'Cédula de ciudadanía' },
    { valor: 'CE', etiqueta: 'Cédula de extranjería' },
    { valor: 'TI', etiqueta: 'Tarjeta de identidad' },
    { valor: 'PASAPORTE', etiqueta: 'Pasaporte' },
    { valor: 'OTRO', etiqueta: 'Otro' },
];

export const TIPOS_CLIENTE: OpcionDeSelect[] = [
    { valor: 'ELECTRONICO', etiqueta: 'Electrónico' },
    { valor: 'REMISIONADO', etiqueta: 'Remisionado' },
];

export const RETENEDOR_FUENTE: OpcionDeSelect[] = [
    { valor: 'NO', etiqueta: 'No' },
    { valor: 'SI', etiqueta: 'Sí' },
];
