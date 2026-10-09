import { usePoll } from '@inertiajs/react';
import { useEffect } from 'react';
import { Badge } from '@/components/ui/badge';
import type { Importacion } from '@/types';

const ETIQUETAS: Record<Importacion['estado'], string> = {
    pendiente: 'En espera',
    procesando: 'Procesando',
    completada: 'Completada',
    fallida: 'Fallida',
};

/** Una importación: estado, barra de avance, resultado y filas rechazadas. */
function FilaImportacion({ importacion }: { importacion: Importacion }) {
    const enCurso =
        importacion.estado === 'pendiente' ||
        importacion.estado === 'procesando';
    const porcentaje =
        importacion.total_filas > 0
            ? Math.round(
                  (importacion.filas_procesadas / importacion.total_filas) *
                      100,
              )
            : 0;

    return (
        <li className="space-y-2 p-3">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <span className="text-sm font-medium">
                    {importacion.tipo && `${importacion.tipo} · `}
                    {importacion.nombre_original}
                </span>
                <Badge
                    variant={
                        importacion.estado === 'fallida'
                            ? 'destructive'
                            : importacion.estado === 'completada'
                              ? 'default'
                              : 'secondary'
                    }
                >
                    {ETIQUETAS[importacion.estado]}
                </Badge>
            </div>

            {enCurso && (
                <div className="space-y-1">
                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                        <div
                            className="h-full bg-primary transition-all"
                            style={{ width: `${porcentaje}%` }}
                        />
                    </div>
                    <p className="text-xs text-muted-foreground">
                        {importacion.filas_procesadas} de{' '}
                        {importacion.total_filas} filas ({porcentaje}%)
                    </p>
                </div>
            )}

            {importacion.estado === 'completada' && (
                <p className="text-sm text-muted-foreground">
                    {importacion.filas_creadas} creadas,{' '}
                    {importacion.filas_actualizadas} actualizadas,{' '}
                    {importacion.filas_omitidas} sin cambios,{' '}
                    {importacion.filas_con_error} con error.
                </p>
            )}

            {importacion.estado === 'fallida' && (
                <p className="text-sm text-destructive">
                    {importacion.mensaje ?? 'No se pudo procesar el archivo.'}
                </p>
            )}

            {importacion.errores.length > 0 && (
                <details className="text-sm">
                    <summary className="cursor-pointer">
                        Ver filas con error
                        {importacion.filas_con_error >
                            importacion.errores.length &&
                            ` (se muestran ${importacion.errores.length})`}
                    </summary>
                    <ul className="mt-2 max-h-40 space-y-1 overflow-y-auto text-muted-foreground">
                        {importacion.errores.map((error) => (
                            <li key={error.fila}>
                                Fila {error.fila}: {error.mensaje}
                            </li>
                        ))}
                    </ul>
                </details>
            )}
        </li>
    );
}

/**
 * Últimas importaciones de un módulo. Mientras alguna siga en curso consulta
 * al servidor cada par de segundos para actualizar el avance; al terminar,
 * deja de consultar.
 */
export default function ImportacionesPanel({
    importaciones,
}: {
    importaciones: Importacion[];
}) {
    const hayEnCurso = importaciones.some(
        (importacion) =>
            importacion.estado === 'pendiente' ||
            importacion.estado === 'procesando',
    );

    const { start, stop } = usePoll(
        2000,
        { only: ['importaciones'] },
        { autoStart: false },
    );

    useEffect(() => {
        if (hayEnCurso) {
            start();
        } else {
            stop();
        }
    }, [hayEnCurso, start, stop]);

    if (importaciones.length === 0) {
        return null;
    }

    return (
        <section className="space-y-2">
            <h2 className="text-sm font-medium">Importaciones recientes</h2>
            <ul className="divide-y rounded-lg border">
                {importaciones.map((importacion) => (
                    <FilaImportacion
                        key={importacion.id}
                        importacion={importacion}
                    />
                ))}
            </ul>
        </section>
    );
}
