import { Head } from '@inertiajs/react';
import TenantController from '@/actions/App/Http/Controllers/Admin/TenantController';
import Heading from '@/components/heading';
import TenantForm from '@/components/tenants/tenant-form';
import { create, index } from '@/routes/admin/tenants';

export default function CreateTenant() {
    return (
        <>
            <Head title="Nuevo tenant" />

            <div className="space-y-6 p-4">
                <Heading
                    title="Nuevo tenant"
                    description="Registra una empresa en la plataforma"
                />
                <TenantForm
                    formAction={TenantController.store.form()}
                    submitLabel="Crear tenant"
                    incluirAdministrador
                />
            </div>
        </>
    );
}

CreateTenant.layout = {
    breadcrumbs: [
        { title: 'Tenants', href: index() },
        { title: 'Nuevo', href: create() },
    ],
};
