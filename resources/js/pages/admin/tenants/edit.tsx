import { Head } from '@inertiajs/react';
import TenantController from '@/actions/App/Http/Controllers/Admin/TenantController';
import Heading from '@/components/heading';
import TenantAdminsSection from '@/components/tenants/tenant-admins-section';
import TenantForm from '@/components/tenants/tenant-form';
import { index } from '@/routes/admin/tenants';
import type { Tenant, TenantAdmin } from '@/types';

type Props = {
    tenant: { data: Tenant };
    administradores: TenantAdmin[];
};

export default function EditTenant({ tenant, administradores }: Props) {
    return (
        <>
            <Head title={`Editar ${tenant.data.nombre}`} />

            <div className="space-y-10 p-4">
                <div className="space-y-6">
                    <Heading
                        title={`Editar ${tenant.data.nombre}`}
                        description="Actualiza los datos del tenant"
                    />
                    <TenantForm
                        formAction={TenantController.update.form(
                            tenant.data.id,
                        )}
                        tenant={tenant.data}
                        submitLabel="Guardar cambios"
                    />
                </div>

                <TenantAdminsSection
                    tenant={tenant.data}
                    administradores={administradores}
                />
            </div>
        </>
    );
}

EditTenant.layout = {
    breadcrumbs: [
        { title: 'Tenants', href: index() },
        { title: 'Editar', href: index() },
    ],
};
