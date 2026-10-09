import { useState } from 'react';
import CrudTable from '@/components/crud/crud-table';
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

    // Errores del servidor para el administrador que ocupa esa posición de la lista.
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

            <CrudTable
                filas={borradores}
                clave={(borrador) => borrador.clave}
                vacio="Todavía no has agregado administradores."
                columnas={[
                    {
                        encabezado: 'Nombre',
                        celda: (borrador) => {
                            const posicion = borradores.indexOf(borrador);

                            return (
                                <div>
                                    <span className="font-medium">
                                        {borrador.name}
                                    </span>
                                    {/* Los datos viajan con el formulario como campos ocultos. */}
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
                                </div>
                            );
                        },
                    },
                    {
                        encabezado: 'Correo',
                        celda: (borrador) => (
                            <span className="text-muted-foreground">
                                {borrador.email}
                            </span>
                        ),
                    },
                    {
                        encabezado: 'Documento',
                        celda: (borrador) => (
                            <span className="text-muted-foreground">
                                {borrador.n_documento}
                            </span>
                        ),
                    },
                    {
                        encabezado: 'Contraseña temporal',
                        celda: (borrador) => (
                            <div className="flex items-center gap-2">
                                <code className="font-mono text-xs">
                                    {borrador.password}
                                </code>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => copiar(borrador.password)}
                                >
                                    {copiado === borrador.password
                                        ? 'Copiada'
                                        : 'Copiar'}
                                </Button>
                            </div>
                        ),
                    },
                ]}
                acciones={(borrador) => (
                    <>
                        <TenantAdminDraftDialog
                            borrador={borrador}
                            onGuardar={(valores) =>
                                reemplazar(borrador.clave, valores)
                            }
                        >
                            <Button type="button" size="sm" variant="outline">
                                Editar
                            </Button>
                        </TenantAdminDraftDialog>
                        <Button
                            type="button"
                            size="sm"
                            variant="destructive"
                            onClick={() => quitar(borrador.clave)}
                        >
                            Quitar
                        </Button>
                    </>
                )}
            />
        </section>
    );
}
