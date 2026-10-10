import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import ProductoController from '@/actions/App/Http/Controllers/Gestion/ProductoController';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import CrudTable from '@/components/crud/crud-table';
import ExportarMenu from '@/components/crud/exportar-menu';
import FilterTabs from '@/components/crud/filter-tabs';
import FormDialog from '@/components/crud/form-dialog';
import ImportacionesPanel from '@/components/crud/importaciones-panel';
import ImportarDialog from '@/components/crud/importar-dialog';
import EliminadosToggle from '@/components/crud/eliminados-toggle';
import PageHeader from '@/components/crud/page-header';
import ProductoFields from '@/components/productos/producto-fields';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { CATEGORIAS_PRODUCTO } from '@/lib/catalogos';
import { index as categoriasIndex } from '@/routes/gestion/categorias';
import { index as medidasIndex } from '@/routes/gestion/medidas';
import { index, restore } from '@/routes/gestion/productos';
import type {
    CategoriaOpcion,
    Importacion,
    ImportacionPendiente,
    Producto,
} from '@/types';

type Props = {
    productos: { data: Producto[] };
    medidas: { id: number; nombre_medida: string }[];
    categorias: CategoriaOpcion[];
    importaciones: { data: Importacion[] };
    importacionPendiente: ImportacionPendiente | null;
};

/** Pestañas del listado: una por tipo de producto, más "Todos". */
const PESTANAS_TIPO = [
    { valor: '', etiqueta: 'Todos' },
    ...CATEGORIAS_PRODUCTO.map((tipo) => ({
        valor: String(tipo.valor),
        etiqueta: tipo.etiqueta,
    })),
];

const moneda = new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
});

/**
 * Catálogo de productos del tenant, con búsqueda y paginación en el navegador. Se eliminan de
 * forma lógica y se restauran desde "Ver eliminados".
 */
export default function ProductosIndex({
    productos,
    medidas,
    categorias,
    importaciones,
    importacionPendiente,
}: Props) {
    const can = useCan();
    const [verEliminados, setVerEliminados] = useState(false);
    const [tipoElegido, setTipoElegido] = useState('');

    // Filas según "Ver eliminados"; de aquí salen también los contadores de las pestañas.
    const filasPorEstado = useMemo(
        () =>
            productos.data.filter(
                (producto) => (producto.deleted_at !== null) === verEliminados,
            ),
        [productos.data, verEliminados],
    );
    const filas = useMemo(
        () =>
            tipoElegido === ''
                ? filasPorEstado
                : filasPorEstado.filter(
                      (producto) => producto.categoria_producto === tipoElegido,
                  ),
        [filasPorEstado, tipoElegido],
    );

    // El formulario necesita estos catálogos; se adaptan al formato de los selects.
    const opcionesMedida = medidas.map((m) => ({
        id: m.id,
        nombre: m.nombre_medida,
    }));

    // Un producto exige medida, categoría y subcategoría: se avisa qué falta crear.
    const faltantes = [
        medidas.length === 0 && {
            etiqueta: 'una medida',
            href: medidasIndex(),
        },
        categorias.every((c) => c.sub_categorias.length === 0) && {
            etiqueta: 'una categoría con subcategorías',
            href: categoriasIndex(),
        },
    ].filter((falta) => falta !== false);

    return (
        <>
            <Head title="Productos" />

            <div className="space-y-6 p-4">
                <PageHeader
                    titulo="Productos"
                    descripcion="Catálogo de lo que vendes"
                    accion={
                        <div className="flex flex-wrap gap-2">
                            <ExportarMenu modulo="productos" />
                            {can('productos.crear') &&
                                faltantes.length === 0 && (
                                    <>
                                        <ImportarDialog
                                            tipos={[
                                                {
                                                    valor: 'productos',
                                                    etiqueta: 'Productos',
                                                },
                                            ]}
                                            pendiente={importacionPendiente}
                                        />
                                        <FormDialog
                                            titulo="Nuevo producto"
                                            accion={ProductoController.store.form()}
                                            etiquetaGuardar="Crear producto"
                                            ancho="xl"
                                            trigger={
                                                <Button>Nuevo producto</Button>
                                            }
                                        >
                                            {({ errors }) => (
                                                <ProductoFields
                                                    errors={errors}
                                                    medidas={opcionesMedida}
                                                    categorias={categorias}
                                                    tipoInicial={
                                                        tipoElegido || undefined
                                                    }
                                                />
                                            )}
                                        </FormDialog>
                                    </>
                                )}
                        </div>
                    }
                />

                {faltantes.length > 0 && (
                    <p className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                        Antes de crear productos necesitas registrar{' '}
                        {faltantes.map((falta, posicion) => (
                            <span key={falta.etiqueta}>
                                {posicion > 0 && ', '}
                                <Link
                                    href={falta.href}
                                    className="font-medium underline underline-offset-4"
                                >
                                    {falta.etiqueta}
                                </Link>
                            </span>
                        ))}
                        .
                    </p>
                )}

                <ImportacionesPanel importaciones={importaciones.data} />

                <FilterTabs
                    activa={tipoElegido}
                    onCambiar={setTipoElegido}
                    pestanas={PESTANAS_TIPO.map((pestana) => ({
                        ...pestana,
                        cantidad:
                            pestana.valor === ''
                                ? filasPorEstado.length
                                : filasPorEstado.filter(
                                      (producto) =>
                                          producto.categoria_producto ===
                                          pestana.valor,
                                  ).length,
                    }))}
                />

                <CrudTable
                    filas={filas}
                    busqueda={{
                        placeholder:
                            'Buscar por código, nombre, marca o código de barras',
                        texto: (p) =>
                            [p.codigo_producto, p.nombre_producto, p.marca, p.codigo_barra].join(' '),
                    }}
                    barra={
                        <EliminadosToggle
                            id="ver-eliminados"
                            etiqueta="Ver eliminados"
                            activo={verEliminados}
                            onCambiar={setVerEliminados}
                        />
                    }
                    clave={(producto) => producto.id}
                    vacio={
                        verEliminados
                            ? 'No hay productos eliminados.'
                            : 'No hay productos que coincidan.'
                    }
                    columnas={[
                        {
                            encabezado: 'N°',
                            celda: (p) => p.numero,
                            valorOrden: (p) => p.numero,
                        },
                        {
                            encabezado: 'Código',
                            valorOrden: (p) => p.codigo_producto,
                            celda: (p) => (
                                <span className="font-mono">
                                    {p.codigo_producto}
                                </span>
                            ),
                        },
                        {
                            encabezado: 'Producto',
                            valorOrden: (p) => p.nombre_producto,
                            celda: (p) => (
                                <div className="flex items-center gap-3">
                                    {p.imagen_url ? (
                                        <img
                                            src={p.imagen_url}
                                            alt=""
                                            className="size-10 rounded-md border object-cover"
                                        />
                                    ) : (
                                        <div className="size-10 rounded-md border bg-muted/40" />
                                    )}
                                    <div>
                                        <p className="font-medium">
                                            {p.nombre_producto}
                                        </p>
                                        {p.marca && (
                                            <p className="text-muted-foreground">
                                                {p.marca}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            ),
                        },
                        {
                            encabezado: 'Categoría',
                            filtro: 'lista',
                            valorFiltro: (p) => p.categoria ?? '',
                            valorOrden: (p) => p.categoria ?? '',
                            celda: (p) => (
                                <div>
                                    <p>{p.categoria ?? '—'}</p>
                                    <p className="text-muted-foreground">
                                        {p.sub_categoria ?? ''}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            encabezado: 'Precio detal',
                            alinear: 'derecha',
                            valorOrden: (p) => p.valor_detal_producto,
                            celda: (p) => moneda.format(p.valor_detal_producto),
                        },
                        {
                            encabezado: 'Stock',
                            alinear: 'derecha',
                            valorOrden: (p) => p.stock,
                            celda: (p) => p.stock,
                        },
                        {
                            encabezado: 'Estado',
                            filtro: 'lista',
                            valorFiltro: (p) =>
                                p.deleted_at ? 'Eliminado' : p.activo ? 'Activo' : 'Inactivo',
                            celda: (p) =>
                                p.deleted_at ? (
                                    <Badge variant="destructive">
                                        Eliminado
                                    </Badge>
                                ) : p.activo ? (
                                    <Badge>Activo</Badge>
                                ) : (
                                    <Badge variant="secondary">Inactivo</Badge>
                                ),
                        },
                    ]}
                    acciones={(producto) =>
                        producto.deleted_at ? (
                            can('productos.eliminar') && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={restore(producto.id)}
                                        as="button"
                                        preserveScroll
                                    >
                                        Restaurar
                                    </Link>
                                </Button>
                            )
                        ) : (
                            <>
                                {can('productos.editar') && (
                                    <FormDialog
                                        titulo={`Editar ${producto.nombre_producto}`}
                                        accion={ProductoController.update.form(
                                            producto.id,
                                        )}
                                        etiquetaGuardar="Guardar cambios"
                                        ancho="xl"
                                        trigger={
                                            <Button size="sm" variant="outline">
                                                Editar
                                            </Button>
                                        }
                                    >
                                        {({ errors }) => (
                                            <ProductoFields
                                                producto={producto}
                                                errors={errors}
                                                medidas={opcionesMedida}
                                                categorias={categorias}
                                            />
                                        )}
                                    </FormDialog>
                                )}
                                {can('productos.eliminar') && (
                                    <ConfirmDeleteDialog
                                        titulo={`¿Eliminar ${producto.nombre_producto}?`}
                                        descripcion="Su historial se conserva y podrás restaurarlo desde 'Ver eliminados'."
                                        accion={ProductoController.destroy.form(
                                            producto.id,
                                        )}
                                    />
                                )}
                            </>
                        )
                    }
                />
            </div>
        </>
    );
}

ProductosIndex.layout = {
    breadcrumbs: [{ title: 'Productos', href: index() }],
};
