import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import EmpresaController from '@/actions/App/Http/Controllers/Gestion/EmpresaController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Empresa } from '@/types';
import BankAccountsField from './bank-accounts-field';
import ImageField from './image-field';
import type { AspectoRecorte } from './image-crop-dialog';

// El logo admite varias proporciones; el ícono siempre es cuadrado.
const ASPECTOS_LOGO: AspectoRecorte[] = [
    { etiqueta: 'Libre' },
    { etiqueta: '1:1', valor: 1 },
    { etiqueta: '4:3', valor: 4 / 3 },
    { etiqueta: '16:9', valor: 16 / 9 },
];
const ASPECTOS_ICONO: AspectoRecorte[] = [{ etiqueta: '1:1', valor: 1 }];

type EmpresaFormProps = {
    /** Empresa guardada; null mientras el tenant no la ha registrado. */
    empresa: Empresa | null;
    /** Nombre con el que arranca el formulario la primera vez. */
    nombreSugerido: string;
    /** Se llama al guardar con éxito (por ejemplo, para cerrar el modal). */
    onSuccess?: () => void;
    /** Botón o enlace para descartar los cambios, junto al de guardar. */
    cancelar?: ReactNode;
};

/**
 * Formulario de los datos de la empresa del tenant: generales, imágenes,
 * cuentas bancarias y opciones de catálogo.
 */
export default function EmpresaForm({
    empresa,
    nombreSugerido,
    onSuccess,
    cancelar,
}: EmpresaFormProps) {
    return (
        <Form
            {...EmpresaController.update.form()}
            options={{ preserveScroll: true }}
            onSuccess={onSuccess}
            className="space-y-8"
        >
            {({ processing, errors }) => (
                <div className="space-y-8">
                    <section className="space-y-6">
                        <Heading variant="small" title="Datos generales" />

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="nombre_empresa">
                                    Nombre de la empresa
                                </Label>
                                <Input
                                    id="nombre_empresa"
                                    name="nombre_empresa"
                                    defaultValue={
                                        empresa?.nombre_empresa ??
                                        nombreSugerido
                                    }
                                    required
                                />
                                <InputError message={errors.nombre_empresa} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="nit_empresa">NIT</Label>
                                <Input
                                    id="nit_empresa"
                                    name="nit_empresa"
                                    defaultValue={empresa?.nit_empresa ?? ''}
                                    placeholder="900123456-7"
                                />
                                <InputError message={errors.nit_empresa} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email_empresa">
                                    Correo electrónico
                                </Label>
                                <Input
                                    id="email_empresa"
                                    type="email"
                                    name="email_empresa"
                                    defaultValue={empresa?.email_empresa ?? ''}
                                />
                                <InputError message={errors.email_empresa} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="telefono_empresa">
                                    Teléfono
                                </Label>
                                <Input
                                    id="telefono_empresa"
                                    name="telefono_empresa"
                                    defaultValue={
                                        empresa?.telefono_empresa ?? ''
                                    }
                                />
                                <InputError message={errors.telefono_empresa} />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="direccion_empresa">
                                    Dirección
                                </Label>
                                <Input
                                    id="direccion_empresa"
                                    name="direccion_empresa"
                                    defaultValue={
                                        empresa?.direccion_empresa ?? ''
                                    }
                                />
                                <InputError
                                    message={errors.direccion_empresa}
                                />
                            </div>
                        </div>
                    </section>

                    <section className="space-y-6">
                        <Heading
                            variant="small"
                            title="Imágenes"
                            description="Aparecen en tus documentos y en la aplicación"
                        />

                        <div className="grid gap-6 sm:grid-cols-2">
                            <ImageField
                                id="logo"
                                name="logo"
                                nombreQuitar="quitar_logo"
                                etiqueta="Logo"
                                ayuda="PNG, JPG, WEBP o SVG. Máximo 2 MB."
                                urlActual={empresa?.logo_url ?? null}
                                error={errors.logo}
                                aspectos={ASPECTOS_LOGO}
                                maxLado={1024}
                            />
                            <ImageField
                                id="icono"
                                name="icono"
                                nombreQuitar="quitar_icono"
                                etiqueta="Ícono"
                                ayuda="Versión cuadrada y pequeña. Máximo 1 MB."
                                urlActual={empresa?.icono_url ?? null}
                                error={errors.icono}
                                aspectos={ASPECTOS_ICONO}
                                maxLado={512}
                            />
                        </div>
                    </section>

                    <section className="space-y-6">
                        <Heading
                            variant="small"
                            title="Cuentas bancarias"
                            description="Se muestran a tus clientes para recibir pagos"
                        />
                        <BankAccountsField
                            inicial={empresa?.cuentas_bancarias ?? []}
                            errors={errors}
                        />
                    </section>

                    <section className="space-y-4">
                        <Heading variant="small" title="Catálogo" />
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="mostrar_productos_sin_inventario"
                                name="mostrar_productos_sin_inventario"
                                defaultChecked={
                                    empresa?.mostrar_productos_sin_inventario ??
                                    true
                                }
                            />
                            <Label htmlFor="mostrar_productos_sin_inventario">
                                Mostrar productos sin inventario
                            </Label>
                        </div>
                    </section>

                    <div className="flex flex-wrap justify-end gap-2">
                        {cancelar}
                        <Button
                            disabled={processing}
                            data-test="guardar-empresa"
                        >
                            {empresa === null
                                ? 'Agregar empresa'
                                : 'Guardar cambios'}
                        </Button>
                    </div>
                </div>
            )}
        </Form>
    );
}
