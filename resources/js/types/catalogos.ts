/** Cuenta del plan único de cuentas (PUC). */
export type Puc = {
    id: number;
    tipo: string;
    cuenta: string;
    subcuenta: string;
    concepto: string;
    descripcion: string | null;
};

export type Medida = {
    id: number;
    nombre_medida: string;
    tipo_medida: 'LONGITUD' | 'PESO' | 'VOLUMEN' | 'CANTIDAD';
    descripcion_medida: string | null;
    productos_count?: number;
};

export type Ruta = {
    id: number;
    ruta: string;
    descripcion: string | null;
    clientes_count?: number;
};

export type Bodega = {
    id: number;
    nombre_bodega: string;
    ubicacion_bodega: string | null;
    productos_count?: number;
    deleted_at: string | null;
};

export type SubCategoria = {
    id: number;
    nombre_sub_categoria: string;
    categoria_id: number;
    productos_count?: number;
};

export type Categoria = {
    id: number;
    nombre_categoria: string;
    productos_count?: number;
    sub_categorias: SubCategoria[];
};

/** Categoría con sus subcategorías, para el select dependiente de productos. */
export type CategoriaOpcion = {
    id: number;
    nombre_categoria: string;
    sub_categorias: { id: number; nombre_sub_categoria: string }[];
};

export type Proveedor = {
    id: number;
    nombre_proveedor: string;
    razon_social_proveedor: string | null;
    nit_proveedor: string;
    tipo_proveedor: 'REMISIONADO' | 'ELECTRONICO';
    categoria_proveedor: 'DECLARANTE' | 'NO_DECLARANTE' | 'RETENEDOR';
    departamento_proveedor: string | null;
    ciudad_proveedor: string | null;
    direccion_proveedor: string | null;
    telefono_proveedor: string | null;
    banco_proveedor: string | null;
    tipo_cuenta_proveedor: 'AHORRO' | 'CORRIENTE' | null;
    numero_cuenta_proveedor: string | null;
    convenio: string | null;
    tiempo_respuesta: string | null;
    fabricante: string | null;
    flete: boolean;
    valor_flete: number;
    rut_url: string | null;
    deleted_at: string | null;
};

export type Producto = {
    id: number;
    numero: number;
    categoria_producto: string;
    codigo_producto: string;
    nombre_producto: string;
    descripcion_producto: string | null;
    costo_producto: number;
    valor_detal_producto: number;
    valor_mayorista_producto: number;
    valor_ferretero_producto: number;
    valor_especial_producto: number;
    iva_producto: number | null;
    medida_id: number;
    categoria_id: number;
    sub_categoria_id: number;
    medida?: string;
    categoria?: string;
    sub_categoria?: string;
    stock: number;
    alerta_producto: number;
    activo: boolean;
    tipo_producto: string | null;
    peso_producto: number | null;
    ubicacion_producto: string | null;
    empaquetado_externo: string | null;
    empaquetado_interno: string | null;
    referencia_producto: string | null;
    codigo_cliente: string | null;
    volumen_producto: string | null;
    tipo_compra: string | null;
    codigo_barra: string | null;
    marca: string | null;
    detalles_producto: string | null;
    codigo_importado: string | null;
    imagen_url: string | null;
    deleted_at: string | null;
};

export type OpcionSelect = { id: number; nombre: string };

/** Cantidad con la que arranca el inventario de un producto en una bodega. */
export type StockInicial = {
    id: number;
    producto_id: number;
    bodega_id: number;
    codigo_producto?: string;
    nombre_producto?: string;
    bodega?: string;
    cantidad: number;
    deleted_at: string | null;
};

export type Cliente = {
    id: number;
    numero: number;
    tipo_documento: string;
    numero_documento: string;
    razon_social: string;
    representante_legal: string | null;
    email: string | null;
    telefono: string | null;
    ciudad: string | null;
    direccion: string | null;
    ruta_id: number | null;
    comercial_id: number | null;
    ruta?: string | null;
    comercial?: string | null;
    categoria: string | null;
    sector: string | null;
    tipo_cliente: 'ELECTRONICO' | 'REMISIONADO';
    retenedor_fuente: 'SI' | 'NO';
    limite_credito: number;
    saldo_cartera: number;
    activo: boolean;
    novedad: string | null;
    rut_url: string | null;
    deleted_at: string | null;
};

/** Un ingrediente de una fórmula: un producto y su cantidad. */
export type DetalleFormula = {
    producto_id: number;
    producto: string;
    medida: string | null;
    cantidad_producto: number;
};

export type Formula = {
    id: number;
    nombre_formula: string;
    descripcion_formula: string | null;
    detalles: DetalleFormula[];
};

/** Producto disponible como ingrediente, con la medida en la que se cuenta. */
export type ProductoIngrediente = {
    id: number;
    nombre: string;
    medida: string | null;
};

/** Seguimiento de un archivo CSV importado en segundo plano. */
export type Importacion = {
    id: number;
    /** Qué se importó (por ejemplo, "Subcategorías"). */
    tipo: string | null;
    nombre_original: string;
    estado: 'pendiente' | 'procesando' | 'completada' | 'fallida';
    modo: 'crear' | 'actualizar';
    delimitador: string | null;
    total_filas: number;
    filas_procesadas: number;
    filas_creadas: number;
    filas_actualizadas: number;
    filas_omitidas: number;
    filas_con_error: number;
    errores: { fila: number; mensaje: string }[];
    mensaje: string | null;
    creada: string | null;
};

/** Archivo ya subido que espera que se relacionen sus columnas con las del sistema. */
export type ImportacionPendiente = {
    id: number;
    etiqueta: string;
    nombre_original: string;
    delimitador: string | null;
    encabezados: string[];
    muestra: (string | null)[][];
    /** Columna del sistema => posición sugerida en el archivo (null si no hay). */
    sugerencia: Record<string, number | null>;
    columnas: {
        clave: string;
        etiqueta: string;
        requerida_crear: boolean;
        requerida_actualizar: boolean;
    }[];
};
