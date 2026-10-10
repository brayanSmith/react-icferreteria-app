import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import StockBodegaController from '@/actions/App/Http/Controllers/Gestion/StockBodegaController';
import CrudTable from '@/components/crud/crud-table';
import FilterTabs from '@/components/crud/filter-tabs';
import ExportarMenu from '@/components/crud/exportar-menu';
import FormDialog from '@/components/crud/form-dialog';
import ImportacionesPanel from '@/components/crud/importaciones-panel';
import ImportarDialog from '@/components/crud/importar-dialog';
import PageHeader from '@/components/crud/page-header';
import CamposMovimiento from '@/components/stock/campos-movimiento';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { index as bodegasIndex } from '@/routes/gestion/bodegas';
import { index as productosIndex } from '@/routes/gestion/productos';
import { index, show } from '@/routes/gestion/stock-bodegas';
import type {
    Importacion,
    ImportacionPendiente,
    OpcionSelect,
    StockBodega,
} from '@/types';

const numero = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 });

type Props = {
    stocks: { data: StockBodega[] };
    productos: OpcionSelect[];
    bodegas: OpcionSelect[];
    importaciones: { data: Importacion[] };
    importacionPendiente: ImportacionPendiente | null;
};

/**
 * Existencias de cada producto en cada bodega, con búsqueda, filtro por bodega
 * y paginación en el navegador. Las existencias se mueven registrando ingresos y salidas; el
 * historial de cada una está en su detalle.
 */
export default function StockBodegasIndex({
    stocks,
    productos,
    bodegas,
    importaciones,
    importacionPendiente,
}: Props) {
    const [bodegaElegida, setBodegaElegida] = useState('');
    const filas = useMemo(
        () =>
            bodegaElegida === ''
                ? stocks.data
                : stocks.data.filter(
                      (stock) => String(stock.bodega_id) === bodegaElegida,
                  ),
        [stocks.data, bodegaElegida],
    );
    const can = useCan();

    // Para registrar un movimiento hacen falta productos y bodegas: se avisa qué falta.
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
            <Head title="Stock por bodega" />

            <div className="space-y-6 p-4">
                <PageHeader
                    titulo="Stock por bodega"
                    descripcion="Existencias de cada producto en cada bodega"
                    accion={
                        <div className="flex flex-wrap gap-2">
                            <ExportarMenu modulo="stock_bodegas" />
                            {can('stock_bodegas.crear') &&
                                faltantes.length === 0 && (
                                    <>
                                        <ImportarDialog
                                            tipos={[
                                                {
                                                    valor: 'movimientos_stock',
                                                    etiqueta:
                                                        'Movimientos de inventario',
                                                },
                                            ]}
                                            pendiente={importacionPendiente}
                                        />
                                        <FormDialog
                                            titulo="Nuevo movimiento"
                                            descripcion="Registra un ingreso o una salida de inventario."
                                            accion={StockBodegaController.store.form()}
                                            etiquetaGuardar="Registrar movimiento"
                                            trigger={
                                                <Button>
                                                    Nuevo movimiento
                                                </Button>
                                            }
                                        >
                                            {({ errors }) => (
                                                <CamposMovimiento
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

                <ImportacionesPanel importaciones={importaciones.data} />

                {faltantes.length > 0 && (
                    <p className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                        Antes de mover inventario necesitas crear{' '}
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

                {bodegas.length > 1 && (
                    <FilterTabs
                        activa={bodegaElegida}
                        onCambiar={setBodegaElegida}
                        pestanas={[
                            { valor: '', etiqueta: 'Todas' },
                            ...bodegas.map((bodega) => ({
                                valor: String(bodega.id),
                                etiqueta: bodega.nombre,
                            })),
                        ]}
                    />
                )}

                <CrudTable
                    filas={filas}
                    busqueda={{
                        placeholder: 'Buscar por código o nombre del producto',
                        texto: (s) =>
                            [s.codigo_producto, s.nombre_producto].join(' '),
                    }}
                    clave={(stock) => stock.id}
                    vacio="No hay existencias que coincidan. Registra un ingreso para empezar."
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
                            encabezado: 'Entradas',
                            alinear: 'derecha',
                            valorOrden: (s) => s.entradas,
                            celda: (s) => numero.format(s.entradas),
                        },
                        {
                            encabezado: 'Salidas',
                            alinear: 'derecha',
                            valorOrden: (s) => s.salidas,
                            celda: (s) => numero.format(s.salidas),
                        },
                        {
                            encabezado: 'Stock',
                            alinear: 'derecha',
                            valorOrden: (s) => s.stock,
                            celda: (s) => (
                                <div className="flex items-center justify-end gap-2">
                                    {s.stock_bajo && (
                                        <Badge variant="destructive">
                                            Stock bajo
                                        </Badge>
                                    )}
                                    <span className="font-medium">
                                        {numero.format(s.stock)}
                                        {s.medida && (
                                            <span className="ml-1 font-normal text-muted-foreground">
                                                {s.medida}
                                            </span>
                                        )}
                                    </span>
                                </div>
                            ),
                        },
                    ]}
                    acciones={(stock) => (
                        <Button asChild size="sm" variant="outline">
                            <Link href={show(stock.id)}>Ver movimientos</Link>
                        </Button>
                    )}
                />
            </div>
        </>
    );
}

StockBodegasIndex.layout = {
    breadcrumbs: [{ title: 'Stock por bodega', href: index() }],
};
