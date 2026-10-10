import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import MovimientoStockController from '@/actions/App/Http/Controllers/Gestion/MovimientoStockController';
import StockBodegaController from '@/actions/App/Http/Controllers/Gestion/StockBodegaController';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import CrudTable from '@/components/crud/crud-table';
import FormDialog from '@/components/crud/form-dialog';
import PageHeader from '@/components/crud/page-header';
import PaginationFooter from '@/components/crud/pagination-footer';
import CamposMovimiento from '@/components/stock/campos-movimiento';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { index } from '@/routes/gestion/stock-bodegas';
import type { MovimientoStock, Paginated, StockBodega } from '@/types';

const numero = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 });

const fecha = new Intl.DateTimeFormat('es-CO', {
    dateStyle: 'short',
    timeStyle: 'short',
});

/** Una cifra del resumen de existencias. */
function Cifra({ titulo, valor }: { titulo: string; valor: string }) {
    return (
        <div className="rounded-lg border p-4">
            <p className="text-sm text-muted-foreground">{titulo}</p>
            <p className="text-2xl font-semibold">{valor}</p>
        </div>
    );
}

type Props = {
    stock: { data: StockBodega };
    movimientos: Paginated<MovimientoStock>;
};

/**
 * Detalle de las existencias de un producto en una bodega: el resumen y el
 * historial de ingresos y salidas. Los movimientos hechos a mano se corrigen o
 * se anulan aquí; los que generó otro documento se corrigen en ese documento.
 */
export default function StockBodegaShow({
    stock: { data: stock },
    movimientos,
}: Props) {
    const can = useCan();

    return (
        <>
            <Head title={`Stock de ${stock.nombre_producto}`} />

            <div className="space-y-6 p-4">
                <Button asChild variant="ghost" size="sm">
                    <Link href={index()}>
                        <ArrowLeft />
                        Volver al stock por bodega
                    </Link>
                </Button>

                <PageHeader
                    titulo={stock.nombre_producto ?? 'Producto'}
                    descripcion={`${stock.codigo_producto} · Bodega ${stock.bodega ?? '—'}`}
                    accion={
                        can('stock_bodegas.crear') && (
                            <FormDialog
                                titulo="Nuevo movimiento"
                                descripcion={`${stock.nombre_producto} en ${stock.bodega}`}
                                accion={StockBodegaController.store.form()}
                                etiquetaGuardar="Registrar movimiento"
                                trigger={<Button>Nuevo movimiento</Button>}
                            >
                                {({ errors }) => (
                                    <CamposMovimiento
                                        errors={errors}
                                        fijo={{
                                            productoId: stock.producto_id,
                                            bodegaId: stock.bodega_id,
                                        }}
                                    />
                                )}
                            </FormDialog>
                        )
                    }
                />

                <div className="grid gap-4 sm:grid-cols-3">
                    <Cifra
                        titulo="Entradas"
                        valor={numero.format(stock.entradas)}
                    />
                    <Cifra
                        titulo="Salidas"
                        valor={numero.format(stock.salidas)}
                    />
                    <div className="rounded-lg border p-4">
                        <p className="text-sm text-muted-foreground">
                            Stock actual
                        </p>
                        <p className="flex items-center gap-2 text-2xl font-semibold">
                            {numero.format(stock.stock)}
                            {stock.medida && (
                                <span className="text-sm font-normal text-muted-foreground">
                                    {stock.medida}
                                </span>
                            )}
                            {stock.stock_bajo && (
                                <Badge variant="destructive">Stock bajo</Badge>
                            )}
                        </p>
                    </div>
                </div>

                <CrudTable
                    filas={movimientos.data}
                    paginar={false}
                    clave={(movimiento) => movimiento.id}
                    vacio="Todavía no hay movimientos."
                    columnas={[
                        {
                            encabezado: 'Fecha',
                            celda: (m) =>
                                m.fecha ? fecha.format(new Date(m.fecha)) : '—',
                        },
                        {
                            encabezado: 'Tipo',
                            celda: (m) =>
                                m.tipo === 'INGRESO' ? (
                                    <Badge>Ingreso</Badge>
                                ) : (
                                    <Badge variant="secondary">Salida</Badge>
                                ),
                        },
                        {
                            encabezado: 'Cantidad',
                            alinear: 'derecha',
                            celda: (m) => (
                                <span className="font-medium">
                                    {m.tipo === 'INGRESO' ? '+' : '−'}
                                    {numero.format(m.cantidad)}
                                </span>
                            ),
                        },
                        { encabezado: 'Motivo', celda: (m) => m.descripcion },
                        {
                            encabezado: 'Origen',
                            celda: (m) => m.origen,
                        },
                        {
                            encabezado: 'Usuario',
                            celda: (m) => m.usuario ?? '—',
                        },
                    ]}
                    acciones={(movimiento) =>
                        movimiento.editable ? (
                            <>
                                {can('stock_bodegas.editar') && (
                                    <FormDialog
                                        titulo="Corregir movimiento"
                                        accion={MovimientoStockController.update.form(
                                            {
                                                stock_bodega: stock.id,
                                                movimiento: movimiento.id,
                                            },
                                        )}
                                        etiquetaGuardar="Guardar cambios"
                                        trigger={
                                            <Button size="sm" variant="outline">
                                                Editar
                                            </Button>
                                        }
                                    >
                                        {({ errors }) => (
                                            <CamposMovimiento
                                                movimiento={movimiento}
                                                errors={errors}
                                            />
                                        )}
                                    </FormDialog>
                                )}
                                {can('stock_bodegas.eliminar') && (
                                    <ConfirmDeleteDialog
                                        titulo="¿Anular este movimiento?"
                                        descripcion="Se deshará su efecto en las existencias. No se puede anular si dejaría el stock en negativo."
                                        etiquetaConfirmar="Anular"
                                        accion={MovimientoStockController.destroy.form(
                                            {
                                                stock_bodega: stock.id,
                                                movimiento: movimiento.id,
                                            },
                                        )}
                                    />
                                )}
                            </>
                        ) : (
                            <span className="text-xs text-muted-foreground">
                                Se corrige en su documento
                            </span>
                        )
                    }
                />

                <PaginationFooter datos={movimientos} />
            </div>
        </>
    );
}

StockBodegaShow.layout = {
    breadcrumbs: [{ title: 'Stock por bodega', href: index() }],
};
