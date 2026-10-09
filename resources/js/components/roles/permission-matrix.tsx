import { Checkbox } from '@/components/ui/checkbox';
import type { CatalogoModulo } from '@/types';

const COLUMNAS = ['Ver', 'Crear', 'Editar', 'Eliminar'];

type PermissionMatrixProps = {
    catalogo: CatalogoModulo[];
    seleccionados: string[];
    onChange: (seleccionados: string[]) => void;
};

/**
 * Matriz de módulos por acciones para elegir los permisos de un rol. Cada
 * módulo tiene un casillero que marca o desmarca todas sus acciones.
 */
export default function PermissionMatrix({
    catalogo,
    seleccionados,
    onChange,
}: PermissionMatrixProps) {
    const marcados = new Set(seleccionados);

    function alternar(permiso: string, marcar: boolean) {
        const nuevos = new Set(marcados);

        if (marcar) {
            nuevos.add(permiso);
        } else {
            nuevos.delete(permiso);
        }

        onChange([...nuevos]);
    }

    function alternarModulo(modulo: CatalogoModulo, marcar: boolean) {
        const nuevos = new Set(marcados);

        for (const { permiso } of modulo.acciones) {
            if (marcar) {
                nuevos.add(permiso);
            } else {
                nuevos.delete(permiso);
            }
        }

        onChange([...nuevos]);
    }

    return (
        <div className="overflow-x-auto rounded-lg border">
            <table className="w-full text-sm">
                <thead className="bg-muted/50 text-left">
                    <tr>
                        <th className="px-3 py-2 font-medium">Módulo</th>
                        {COLUMNAS.map((columna) => (
                            <th
                                key={columna}
                                className="px-3 py-2 text-center font-medium"
                            >
                                {columna}
                            </th>
                        ))}
                        <th className="px-3 py-2 text-center font-medium">
                            Todo
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {catalogo.map((modulo) => {
                        const todosMarcados = modulo.acciones.every(
                            ({ permiso }) => marcados.has(permiso),
                        );

                        return (
                            <tr key={modulo.modulo}>
                                <td className="px-3 py-2 font-medium">
                                    {modulo.etiqueta}
                                </td>
                                {COLUMNAS.map((columna) => {
                                    const accion = modulo.acciones.find(
                                        (candidata) =>
                                            candidata.etiqueta === columna,
                                    );

                                    return (
                                        <td
                                            key={columna}
                                            className="px-3 py-2 text-center"
                                        >
                                            {accion && (
                                                <Checkbox
                                                    aria-label={`${columna} ${modulo.etiqueta}`}
                                                    checked={marcados.has(
                                                        accion.permiso,
                                                    )}
                                                    onCheckedChange={(valor) =>
                                                        alternar(
                                                            accion.permiso,
                                                            valor === true,
                                                        )
                                                    }
                                                />
                                            )}
                                        </td>
                                    );
                                })}
                                <td className="px-3 py-2 text-center">
                                    <Checkbox
                                        aria-label={`Todo ${modulo.etiqueta}`}
                                        checked={todosMarcados}
                                        onCheckedChange={(valor) =>
                                            alternarModulo(
                                                modulo,
                                                valor === true,
                                            )
                                        }
                                    />
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
