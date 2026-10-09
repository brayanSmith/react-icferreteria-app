import { Head, Link } from '@inertiajs/react';
import ProductoController from '@/actions/App/Http/Controllers/Gestion/ProductoController';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import CrudTable from '@/components/crud/crud-table';
import FormDialog from '@/components/crud/form-dialog';
import ListFilters from '@/components/crud/list-filters';
import PageHeader from '@/components/crud/page-header';
import PaginationFooter from '@/components/crud/pagination-footer';
import ProductoFields from '@/components/productos/producto-fields';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { index as bodegasIndex } from '@/routes/gestion/bodegas';
import { index as categoriasIndex } from '@/routes/gestion/categorias';
import { index as medidasIndex } from '@/routes/gestion/medidas';
import { index, restore } from '@/routes/gestion/productos';
import type { CategoriaOpcion, Paginated, Producto } from '@/types';

type Props = {
    productos: Paginated<Producto>;
    filtros: { buscar: string; eliminados: boolean };
    medidas: { id: number; nombre_medida: string }[];
    bodegas: { id: number; nombre_bodega: string }[];
    categorias: CategoriaOpcion[];
};

const moneda = new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
});

/**
 * Catálogo de productos del tenant, con búsqueda y paginación. Se eliminan de
 * forma lógica y se restauran desde "Ver eliminados".
 */
export default function ProductosIndex({
    productos,
    filtros,
    medidas,
    bodegas,
    categorias,
}: Props) {
    const can = useCan();

    // El formulario necesita estos catálogos; se adaptan al formato de los selects.
    const opcionesMedida = medidas.map((m) => ({
        id: m.id,
        nombre: m.nombre_medida,
    }));
    const opcionesBodega = bodegas.map((b) => ({
        id: b.id,
        nombre: b.nombre_bodega,
    }));

    // Un producto exige medida, bodega, categoría y subcategoría: se avisa qué falta crear.
    const faltantes = [
        medidas.length === 0 && {
            etiqueta: 'una medida',
            href: medidasIndex(),
        },
        bodegas.length === 0 && {
            etiqueta: 'una bodega',
            href: bodegasIndex(),
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
                        can('productos.crear') &&
                        faltantes.length === 0 && (
                            <FormDialog
                                titulo="Nuevo producto"
                                accion={ProductoController.store.form()}
                                etiquetaGuardar="Crear producto"
                                ancho="xl"
                                trigger={<Button>Nuevo producto</Button>}
                            >
                                {({ errors }) => (
                                    <ProductoFields
                                        errors={errors}
                                        medidas={opcionesMedida}
                                        bodegas={opcionesBodega}
                                        categorias={categorias}
                                    />
                                )}
                            </FormDialog>
                        )
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

                <ListFilters
                    url={index.url()}
                    buscar={filtros.buscar}
                    placeholder="Buscar por código, nombre, marca o código de barras"
                    eliminados={{
                        parametro: 'eliminados',
                        etiqueta: 'Ver eliminados',
                        activo: filtros.eliminados,
                    }}
                />

                <CrudTable
                    filas={productos.data}
                    clave={(producto) => producto.id}
                    vacio={
                        filtros.eliminados
                            ? 'No hay productos eliminados.'
                            : 'No hay productos que coincidan.'
                    }
                    columnas={[
                        { encabezado: 'N°', celda: (p) => p.numero },
                        {
                            encabezado: 'Código',
                            celda: (p) => (
                                <span className="font-mono">
                                    {p.codigo_producto}
                                </span>
                            ),
                        },
                        {
                            encabezado: 'Producto',
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
                            celda: (p) => moneda.format(p.valor_detal_producto),
                        },
                        {
                            encabezado: 'Stock',
                            alinear: 'derecha',
                            celda: (p) => p.stock,
                        },
                        {
                            encabezado: 'Estado',
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
                                                bodegas={opcionesBodega}
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

                <PaginationFooter datos={productos} />
            </div>
        </>
    );
}

ProductosIndex.layout = {
    breadcrumbs: [{ title: 'Productos', href: index() }],
};
