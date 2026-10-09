import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Tenant, TenantAdmin } from '@/types';
import DeleteTenantAdminDialog from './delete-tenant-admin-dialog';
import TenantAdminDialog from './tenant-admin-dialog';

export default function TenantAdminsTable({
    tenant,
    administradores,
}: {
    tenant: Tenant;
    administradores: TenantAdmin[];
}) {
    if (administradores.length === 0) {
        return (
            <p className="rounded-lg border p-6 text-center text-sm text-muted-foreground">
                Este tenant todavía no tiene administradores.
            </p>
        );
    }

    return (
        <div className="overflow-x-auto rounded-lg border">
            <table className="w-full text-sm">
                <thead className="bg-muted/50 text-left">
                    <tr>
                        <th className="px-4 py-3 font-medium">Nombre</th>
                        <th className="px-4 py-3 font-medium">Correo</th>
                        <th className="px-4 py-3 font-medium">Documento</th>
                        <th className="px-4 py-3 font-medium">Contraseña</th>
                        <th className="px-4 py-3 text-right font-medium">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {administradores.map((administrador) => (
                        <tr key={administrador.id}>
                            <td className="px-4 py-3 font-medium">
                                {administrador.name}
                            </td>
                            <td className="px-4 py-3 text-muted-foreground">
                                {administrador.email}
                            </td>
                            <td className="px-4 py-3 text-muted-foreground">
                                {administrador.n_documento}
                            </td>
                            <td className="px-4 py-3">
                                {administrador.debe_cambiar_password ? (
                                    <Badge variant="secondary">Temporal</Badge>
                                ) : (
                                    <Badge variant="outline">Definida</Badge>
                                )}
                            </td>
                            <td className="px-4 py-3">
                                <div className="flex justify-end gap-2">
                                    <TenantAdminDialog
                                        tenant={tenant}
                                        admin={administrador}
                                    >
                                        <Button size="sm" variant="outline">
                                            Editar
                                        </Button>
                                    </TenantAdminDialog>
                                    <DeleteTenantAdminDialog
                                        tenant={tenant}
                                        admin={administrador}
                                    />
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
