import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { useClipboard } from '@/hooks/use-clipboard';
import type { TenantAdminBorrador, TenantAdminValores } from '@/types';
import TenantAdminDraftDialog from './tenant-admin-draft-dialog';

const CAMPOS: (keyof TenantAdminValores)[] = [
    'name',
    'email',
    'n_documento',
    'password',
];

/**
 * Administradores del tenant que se está creando. Se arman en pantalla (tabla
 * y modal, igual que al editar) y viajan al servidor como campos ocultos
 * `admins[i][campo]` junto con el resto del formulario.
 */
export default function TenantAdminsDraftSection({
    errors,
}: {
    errors: Record<string, string>;
}) {
    const [borradores, setBorradores] = useState<TenantAdminBorrador[]>([]);
    const [siguienteClave, setSiguienteClave] = useState(1);
    const [copiado, copiar] = useClipboard();

    function agregar(valores: TenantAdminValores) {
        setBorradores((actuales) => [
            ...actuales,
            { ...valores, clave: siguienteClave },
        ]);
        setSiguienteClave((actual) => actual + 1);
    }

    function reemplazar(clave: number, valores: TenantAdminValores) {
        setBorradores((actuales) =>
            actuales.map((borrador) =>
                borrador.clave === clave ? { ...valores, clave } : borrador,
            ),
        );
    }

    function quitar(clave: number) {
        setBorradores((actuales) =>
            actuales.filter((borrador) => borrador.clave !== clave),
        );
    }

    const erroresDe = (posicion: number) =>
        Object.entries(errors)
            .filter(([clave]) => clave.startsWith(`admins.${posicion}.`))
            .map(([, mensaje]) => mensaje);

    return (
        <section className="space-y-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <Heading
                    variant="small"
                    title="Administradores"
                    description="Agrega al menos uno; podrás sumar más después"
                />
                <TenantAdminDraftDialog onGuardar={agregar}>
                    <Button type="button">Agregar administrador</Button>
                </TenantAdminDraftDialog>
            </div>

            <InputError message={errors.admins} />

            {borradores.length === 0 ? (
                <p className="rounded-lg border p-6 text-center text-sm text-muted-foreground">
                    Todavía no has agregado administradores.
                </p>
            ) : (
                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Nombre
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Correo
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Documento
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Contraseña temporal
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {borradores.map((borrador, posicion) => (
                                <tr key={borrador.clave} className="align-top">
                                    <td className="px-4 py-3 font-medium">
                                        {borrador.name}
                                        {CAMPOS.map((campo) => (
                                            <input
                                                key={campo}
                                                type="hidden"
                                                name={`admins[${posicion}][${campo}]`}
                                                value={borrador[campo]}
                                            />
                                        ))}
                                        {erroresDe(posicion).map((mensaje) => (
                                            <InputError
                                                key={mensaje}
                                                message={mensaje}
                                                className="mt-1 font-normal"
                                            />
                                        ))}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {borrador.email}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {borrador.n_documento}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <code className="font-mono text-xs">
                                                {borrador.password}
                                            </code>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    copiar(borrador.password)
                                                }
                                            >
                                                {copiado === borrador.password
                                                    ? 'Copiada'
                                                    : 'Copiar'}
                                            </Button>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-2">
                                            <TenantAdminDraftDialog
                                                borrador={borrador}
                                                onGuardar={(valores) =>
                                                    reemplazar(
                                                        borrador.clave,
                                                        valores,
                                                    )
                                                }
                                            >
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    Editar
                                                </Button>
                                            </TenantAdminDraftDialog>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="destructive"
                                                onClick={() =>
                                                    quitar(borrador.clave)
                                                }
                                            >
                                                Quitar
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </section>
    );
}
