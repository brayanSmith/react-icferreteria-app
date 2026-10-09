import type { ReactNode } from 'react';
import Heading from '@/components/heading';

/**
 * Encabezado de las pantallas de CRUD: título y descripción a la izquierda y,
 * a la derecha, la acción principal (normalmente el botón de crear).
 */
export default function PageHeader({
    titulo,
    descripcion,
    accion,
}: {
    titulo: string;
    descripcion: string;
    accion?: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-4">
            <Heading title={titulo} description={descripcion} />
            {accion}
        </div>
    );
}
