import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { slugify } from '@/lib/slugify';
import { index } from '@/routes/admin/tenants';
import type { Tenant } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';
import TenantAdminsDraftSection from './tenant-admins-draft-section';

type TenantFormProps = {
    /** Acción de Wayfinder: `TenantController.store.form()` o `.update.form(id)`. */
    formAction: RouteFormDefinition<'post'>;
    tenant?: Tenant;
    submitLabel: string;
    /** Solo al crear: pide también los datos de los administradores. */
    incluirAdministrador?: boolean;
};

/**
 * Formulario compartido por las pantallas de crear y editar un tenant.
 */
export default function TenantForm({
    formAction,
    tenant,
    submitLabel,
    incluirAdministrador = false,
}: TenantFormProps) {
    const esEdicion = tenant !== undefined;

    const [nombre, setNombre] = useState(tenant?.nombre ?? '');
    const [slug, setSlug] = useState(tenant?.slug ?? '');
    // Al crear, el slug sigue al nombre hasta que alguien lo edita a mano.
    // Al editar nunca se toca solo, para no cambiar un identificador en uso.
    const [slugEditadoAMano, setSlugEditadoAMano] = useState(esEdicion);

    function cambiarNombre(valor: string) {
        setNombre(valor);

        if (!slugEditadoAMano) {
            setSlug(slugify(valor));
        }
    }

    function cambiarSlug(valor: string) {
        setSlug(valor);
        setSlugEditadoAMano(valor !== '');
    }

    return (
        <Form {...formAction} className="max-w-3xl space-y-6">
            {({ processing, errors }) => (
                <>
                    <div className="grid items-start gap-4 sm:grid-cols-[1fr_1fr_auto]">
                        <div className="grid gap-2">
                            <Label htmlFor="nombre">Nombre</Label>
                            <Input
                                id="nombre"
                                name="nombre"
                                value={nombre}
                                onChange={(event) =>
                                    cambiarNombre(event.target.value)
                                }
                                required
                                autoFocus
                                placeholder="Ferretería El Tornillo"
                            />
                            <InputError message={errors.nombre} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="slug">Identificador (slug)</Label>
                            <Input
                                id="slug"
                                name="slug"
                                value={slug}
                                onChange={(event) =>
                                    cambiarSlug(event.target.value)
                                }
                                placeholder="ferreteria-el-tornillo"
                            />
                            <p className="text-sm text-muted-foreground">
                                {esEdicion
                                    ? 'Cambiarlo puede afectar su uso.'
                                    : 'Se llena con el nombre.'}
                            </p>
                            <InputError message={errors.slug} />
                        </div>

                        <div className="flex items-center gap-2 sm:mt-8 sm:h-9">
                            <Checkbox
                                id="activo"
                                name="activo"
                                defaultChecked={tenant?.activo ?? true}
                            />
                            <Label htmlFor="activo">Activo</Label>
                            <InputError message={errors.activo} />
                        </div>
                    </div>

                    {incluirAdministrador && (
                        <TenantAdminsDraftSection errors={errors} />
                    )}

                    <div className="flex items-center gap-3">
                        <Button
                            disabled={processing}
                            data-test="guardar-tenant"
                        >
                            {submitLabel}
                        </Button>
                        <Button asChild variant="ghost">
                            <Link href={index()}>Cancelar</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
