import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import StockInicialController from '@/actions/App/Http/Controllers/Gestion/StockInicialController';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import CrudTable from '@/components/crud/crud-table';
import ExportarMenu from '@/components/crud/exportar-menu';
import FormDialog from '@/components/crud/form-dialog';
import ImportacionesPanel from '@/components/crud/importaciones-panel';
import ImportarDialog from '@/components/crud/importar-dialog';
import EliminadosToggle from '@/components/crud/eliminados-toggle';
import PageHeader from '@/components/crud/page-header';
import SelectField from '@/components/crud/select-field';
import TextField from '@/components/crud/text-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { index as bodegasIndex } from '@/routes/gestion/bodegas';
import { index as productosIndex } from '@/routes/gestion/productos';
import { index, restore } from '@/routes/gestion/stock-inicial';
import type {
    Importacion,
    ImportacionPendiente,
    OpcionSelect,
    StockInicial,
} from '@/types';

const numero = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 });

type CamposProps = {
    stock?: StockInicial;
    errors: Record<string, string>;
    productos: OpcionSelect[];
    bodegas: OpcionSelect[];
};

/**
 * Campos del formulario de stock inicial. Al crear se elige producto y bodega;
 * al editar solo cambia la cantidad, porque el producto y la bodega identifican
 * el registro.
 */
function CamposStock({ stock, errors, productos, bodegas }: CamposProps) {
    return (
        <>
            {stock ? (
                <div className="space-y-1 rounded-lg border bg-muted/30 p-3 text-sm">
                    <p>
                        <span className="text-muted-foreground">
                            Producto:{' '}
                        </span>
                        <span className="font-medium">
                            {stock.codigo_producto} - {stock.nombre_producto}
                        </span>
                    </p>
                    <p>
                        <span className="text-muted-foreground">Bodega: </span>
                        <span className="font-medium">{stock.bodega}</span>
                    </p>
                </div>
            ) : (
                <>
                    <SelectField
                        name="producto_id"
                        etiqueta="Producto"
                        opciones={productos.map((p) => ({
                            valor: p.id,
                            etiqueta: p.nombre,
                        }))}
                        placeholder="Selecciona un producto"
                        error={errors.producto_id}
                    />
                    <SelectField
                        name="bodega_id"
                        etiqueta="Bodega"
                        opciones={bodegas.map((b) => ({
                            valor: b.id,
                            etiqueta: b.nombre,
                        }))}
                        placeholder="Selecciona una bodega"
                        error={errors.bodega_id}
                    />
                </>
            )}
            <TextField
                name="cantidad"
                etiqueta="Cantidad"
                type="number"
                min={0}
                step="0.01"
                defaultValue={stock?.cantidad ?? ''}
                required
                error={errors.cantidad}
            />
        </>
    );
}

type Props = {
    stocks: { data: StockInicial[] };
    productos: OpcionSelect[];
    bodegas: OpcionSelect[];
    importaciones: { data: Importacion[] };
    importacionPendiente: ImportacionPendiente | null;
};

/**
 * Stock inicial de los productos por bodega, con búsqueda y paginación en el navegador. Se
 * elimina de forma lógica y se restaura desde "Ver eliminados".
 */
export default function StockInicialIndex({
    stocks,
    productos,
    bodegas,
    importaciones,
    importacionPendiente,
}: Props) {
    const can = useCan();
    const [verEliminados, setVerEliminados] = useState(false);
    const filas = useMemo(
        () =>
            stocks.data.filter(
                (stock) => (stock.deleted_at !== null) === verEliminados,
            ),
        [stocks.data, verEliminados],
    );

    // Para registrar un stock inicial hacen falta productos y bodegas: se avisa qué falta.
    const faltantes = [
        productos.length === 0 && {
            etiqueta: 'un producto',
            href: productosIndex(),
        },
        bodegas.length === 0 && {
            etiqueta: 'una bodega',
            href: bodegasIndex(),
        },
    ].filter((falta) => falta !== false);

    return (
        <>
            <Head title="Stock inicial" />

            <div className="space-y-6 p-4">
                <PageHeader
                    titulo="Stock inicial"
                    descripcion="Cantidad con la que arranca cada producto en cada bodega. Cada registro genera su ingreso en Stock por bodega."
                    accion={
                        <div className="flex flex-wrap gap-2">
                            <ExportarMenu modulo="stock_inicial" />
                            {can('stock_inicial.crear') &&
                                faltantes.length === 0 && (
                                    <>
                                        <ImportarDialog
                                            tipos={[
                                                {
                                                    valor: 'stock_inicial',
                                                    etiqueta: 'Stock inicial',
                                                },
                                            ]}
                                            pendiente={importacionPendiente}
                                        />
                                        <FormDialog
                                            titulo="Nuevo stock inicial"
                                            accion={StockInicialController.store.form()}
                                            etiquetaGuardar="Registrar stock"
                                            trigger={
                                                <Button>
                                                    Nuevo stock inicial
                                                </Button>
                                            }
                                        >
                                            {({ errors }) => (
                                                <CamposStock
                                                    errors={errors}
                                                    productos={productos}
                                                    bodegas={bodegas}
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
                        Antes de registrar stock necesitas crear{' '}
                        {faltantes.map((falta, posicion) => (
                            <span key={falta.etiqueta}>
                                {posicion > 0 && ' y '}
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

                <CrudTable
                    filas={filas}
                    busqueda={{
                        placeholder: 'Buscar por código o nombre del producto',
                        texto: (s) =>
                            [s.codigo_producto, s.nombre_producto, s.bodega].join(' '),
                    }}
                    barra={
                        <EliminadosToggle
                            id="ver-eliminados"
                            etiqueta="Ver eliminados"
                            activo={verEliminados}
                            onCambiar={setVerEliminados}
                        />
                    }
                    clave={(stock) => stock.id}
                    vacio={
                        verEliminados
                            ? 'No hay registros eliminados.'
                            : 'No hay stock inicial que coincida.'
                    }
                    columnas={[
                        {
                            encabezado: 'Producto',
                            valorOrden: (s) => s.nombre_producto ?? '',
                            celda: (s) => (
                                <div>
                                    <p className="font-medium">
                                        {s.nombre_producto}
                                    </p>
                                    <p className="font-mono text-muted-foreground">
                                        {s.codigo_producto}
                                    </p>
                                </div>
                            ),
                        },
                        {
                            encabezado: 'Bodega',
                            celda: (s) => s.bodega ?? '—',
                            filtro: 'lista',
                            valorOrden: (s) => s.bodega ?? '',
                        },
                        {
                            encabezado: 'Cantidad',
                            alinear: 'derecha',
                            valorOrden: (s) => s.cantidad,
                            celda: (s) => numero.format(s.cantidad),
                        },
                        {
                            encabezado: 'Estado',
                            filtro: 'lista',
                            valorFiltro: (s) => (s.deleted_at ? 'Eliminado' : 'Activo'),
                            celda: (s) =>
                                s.deleted_at ? (
                                    <Badge variant="destructive">
                                        Eliminado
                                    </Badge>
                                ) : (
                                    <Badge>Activo</Badge>
                                ),
                        },
                    ]}
                    acciones={(stock) =>
                        stock.deleted_at ? (
                            can('stock_inicial.eliminar') && (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={restore(stock.id)}
                                        as="button"
                                        preserveScroll
                                    >
                                        Restaurar
                                    </Link>
                                </Button>
                            )
                        ) : (
                            <>
                                {can('stock_inicial.editar') && (
                                    <FormDialog
                                        titulo="Editar stock inicial"
                                        accion={StockInicialController.update.form(
                                            stock.id,
                                        )}
                                        etiquetaGuardar="Guardar cambios"
                                        trigger={
                                            <Button size="sm" variant="outline">
                                                Editar
                                            </Button>
                                        }
                                    >
                                        {({ errors }) => (
                                            <CamposStock
                                                stock={stock}
                                                errors={errors}
                                                productos={productos}
                                                bodegas={bodegas}
                                            />
                                        )}
                                    </FormDialog>
                                )}
                                {can('stock_inicial.eliminar') && (
                                    <ConfirmDeleteDialog
                                        titulo="¿Eliminar este stock inicial?"
                                        descripcion="Se anulará su ingreso en Stock por bodega, siempre que esa mercancía no haya salido. Podrás restaurarlo desde 'Ver eliminados'."
                                        accion={StockInicialController.destroy.form(
                                            stock.id,
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

StockInicialIndex.layout = {
    breadcrumbs: [{ title: 'Stock inicial', href: index() }],
};
