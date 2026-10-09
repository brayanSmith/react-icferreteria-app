import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Empresa } from '@/types';

function Dato({ etiqueta, valor }: { etiqueta: string; valor: string | null }) {
    return (
        <div>
            <dt className="text-sm text-muted-foreground">{etiqueta}</dt>
            <dd className="font-medium">{valor || '—'}</dd>
        </div>
    );
}

function Imagen({ etiqueta, url }: { etiqueta: string; url: string | null }) {
    return (
        <div className="space-y-2">
            <p className="text-sm text-muted-foreground">{etiqueta}</p>
            <div className="flex size-24 items-center justify-center overflow-hidden rounded-lg border bg-muted/30">
                {url ? (
                    <img
                        src={url}
                        alt={etiqueta}
                        className="size-full object-contain"
                    />
                ) : (
                    <span className="px-2 text-center text-xs text-muted-foreground">
                        Sin imagen
                    </span>
                )}
            </div>
        </div>
    );
}

/**
 * Resumen de solo lectura de la empresa del tenant.
 */
export default function EmpresaCard({ empresa }: { empresa: Empresa }) {
    return (
        <Card className="max-w-3xl">
            <CardHeader>
                <CardTitle className="text-xl">
                    {empresa.nombre_empresa}
                </CardTitle>
            </CardHeader>

            <CardContent className="space-y-8">
                <dl className="grid gap-4 sm:grid-cols-2">
                    <Dato etiqueta="NIT" valor={empresa.nit_empresa} />
                    <Dato
                        etiqueta="Correo electrónico"
                        valor={empresa.email_empresa}
                    />
                    <Dato
                        etiqueta="Teléfono"
                        valor={empresa.telefono_empresa}
                    />
                    <Dato
                        etiqueta="Dirección"
                        valor={empresa.direccion_empresa}
                    />
                </dl>

                <div className="flex flex-wrap gap-6">
                    <Imagen etiqueta="Logo" url={empresa.logo_url} />
                    <Imagen etiqueta="Ícono" url={empresa.icono_url} />
                </div>

                <section className="space-y-3">
                    <h3 className="text-sm font-medium">Cuentas bancarias</h3>
                    {empresa.cuentas_bancarias.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay cuentas registradas.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-lg border">
                            {empresa.cuentas_bancarias.map(
                                (cuenta, posicion) => (
                                    <li
                                        key={posicion}
                                        className="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {cuenta.banco} ·{' '}
                                                {cuenta.tipo === 'AHORRO'
                                                    ? 'Ahorros'
                                                    : 'Corriente'}
                                            </p>
                                            {cuenta.titular && (
                                                <p className="text-muted-foreground">
                                                    {cuenta.titular}
                                                </p>
                                            )}
                                        </div>
                                        <code className="font-mono">
                                            {cuenta.numero}
                                        </code>
                                    </li>
                                ),
                            )}
                        </ul>
                    )}
                </section>

                <Badge variant="secondary">
                    {empresa.mostrar_productos_sin_inventario
                        ? 'Muestra productos sin inventario'
                        : 'Oculta productos sin inventario'}
                </Badge>
            </CardContent>
        </Card>
    );
}
