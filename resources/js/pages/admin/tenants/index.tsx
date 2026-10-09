import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import PaginationLinks from '@/components/pagination-links';
import TenantFiltersBar from '@/components/tenants/tenant-filters';
import TenantsTable from '@/components/tenants/tenants-table';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/admin/tenants';
import type { Paginated, Tenant, TenantFilters } from '@/types';

type Props = {
    tenants: Paginated<Tenant>;
    filtros: TenantFilters;
};

export default function TenantsIndex({ tenants, filtros }: Props) {
    return (
        <>
            <Head title="Tenants" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Tenants"
                        description="Empresas que usan la plataforma"
                    />
                    <Button asChild>
                        <Link href={create()}>Nuevo tenant</Link>
                    </Button>
                </div>

                <TenantFiltersBar filtros={filtros} />

                <TenantsTable tenants={tenants.data} />

                <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground">
                    <span>
                        {tenants.meta.total === 0
                            ? 'Sin resultados'
                            : `Mostrando ${tenants.meta.from}–${tenants.meta.to} de ${tenants.meta.total}`}
                    </span>
                    <PaginationLinks links={tenants.meta.links} />
                </div>
            </div>
        </>
    );
}

TenantsIndex.layout = {
    breadcrumbs: [{ title: 'Tenants', href: index() }],
};
