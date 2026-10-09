import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import type { Tenant, TenantAdmin } from '@/types';
import TenantAdminDialog from './tenant-admin-dialog';
import TenantAdminsTable from './tenant-admins-table';

/**
 * Administradores de un tenant existente: tabla con los actuales y un modal
 * para agregar más.
 */
export default function TenantAdminsSection({
    tenant,
    administradores,
}: {
    tenant: Tenant;
    administradores: TenantAdmin[];
}) {
    return (
        <section className="space-y-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <Heading
                    variant="small"
                    title="Administradores"
                    description="Pueden gestionar los usuarios y la empresa de este tenant"
                />
                <TenantAdminDialog tenant={tenant}>
                    <Button>Agregar administrador</Button>
                </TenantAdminDialog>
            </div>

            <TenantAdminsTable
                tenant={tenant}
                administradores={administradores}
            />
        </section>
    );
}
