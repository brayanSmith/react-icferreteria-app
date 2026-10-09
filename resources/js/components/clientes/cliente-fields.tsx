import { useState } from 'react';
import type { ReactNode } from 'react';
import RadioGroupField from '@/components/crud/radio-group-field';
import SelectField from '@/components/crud/select-field';
import TextField from '@/components/crud/text-field';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    RETENEDOR_FUENTE,
    TIPOS_CLIENTE,
    TIPOS_DOCUMENTO,
} from '@/lib/catalogos';
import type { Cliente, OpcionSelect } from '@/types';

/** Bloque con título dentro del formulario. */
function Seccion({
    titulo,
    children,
}: {
    titulo: string;
    children: ReactNode;
}) {
    return (
        <fieldset className="space-y-4 rounded-lg border p-4">
            <legend className="px-2 text-sm font-medium">{titulo}</legend>
            {children}
        </fieldset>
    );
}

type ClienteFieldsProps = {
    cliente?: Cliente;
    errors: Record<string, string>;
    rutas: OpcionSelect[];
    comerciales: OpcionSelect[];
};

/**
 * Campos del formulario de cliente, agrupados por tema. Los usan los modales de
 * crear y editar.
 */
export default function ClienteFields({
    cliente,
    errors,
    rutas,
    comerciales,
}: ClienteFieldsProps) {
    const [quitarRut, setQuitarRut] = useState(false);

    return (
        <div className="space-y-4">
            <Seccion titulo="Identificación">
                <div className="grid gap-4 sm:grid-cols-2">
                    <SelectField
                        name="tipo_documento"
                        etiqueta="Tipo de documento"
                        defaultValue={cliente?.tipo_documento ?? 'NIT'}
                        opciones={TIPOS_DOCUMENTO}
                        error={errors.tipo_documento}
                    />
                    <TextField
                        name="numero_documento"
                        etiqueta="Número de documento"
                        defaultValue={cliente?.numero_documento}
                        required
                        error={errors.numero_documento}
                    />
                </div>
                <TextField
                    name="razon_social"
                    etiqueta="Razón social o nombre"
                    defaultValue={cliente?.razon_social}
                    required
                    error={errors.razon_social}
                />
                <TextField
                    name="representante_legal"
                    etiqueta="Representante legal (opcional)"
                    defaultValue={cliente?.representante_legal ?? ''}
                    error={errors.representante_legal}
                />
            </Seccion>

            <Seccion titulo="Contacto">
                <div className="grid gap-4 sm:grid-cols-2">
                    <TextField
                        name="email"
                        etiqueta="Correo electrónico"
                        type="email"
                        defaultValue={cliente?.email ?? ''}
                        error={errors.email}
                    />
                    <TextField
                        name="telefono"
                        etiqueta="Teléfono"
                        defaultValue={cliente?.telefono ?? ''}
                        error={errors.telefono}
                    />
                    <TextField
                        name="ciudad"
                        etiqueta="Ciudad"
                        defaultValue={cliente?.ciudad ?? ''}
                        error={errors.ciudad}
                    />
                    <TextField
                        name="direccion"
                        etiqueta="Dirección"
                        defaultValue={cliente?.direccion ?? ''}
                        error={errors.direccion}
                    />
                </div>
            </Seccion>

            <Seccion titulo="Comercial">
                <div className="grid gap-4 sm:grid-cols-2">
                    <SelectField
                        name="ruta_id"
                        etiqueta="Ruta"
                        defaultValue={cliente?.ruta_id ?? ''}
                        opciones={rutas.map((r) => ({
                            valor: r.id,
                            etiqueta: r.nombre,
                        }))}
                        placeholder="Sin ruta"
                        error={errors.ruta_id}
                    />
                    <SelectField
                        name="comercial_id"
                        etiqueta="Comercial"
                        defaultValue={cliente?.comercial_id ?? ''}
                        opciones={comerciales.map((c) => ({
                            valor: c.id,
                            etiqueta: c.nombre,
                        }))}
                        placeholder="Sin comercial"
                        error={errors.comercial_id}
                    />
                    <TextField
                        name="categoria"
                        etiqueta="Categoría (opcional)"
                        defaultValue={cliente?.categoria ?? ''}
                        error={errors.categoria}
                    />
                    <TextField
                        name="sector"
                        etiqueta="Sector (opcional)"
                        defaultValue={cliente?.sector ?? ''}
                        error={errors.sector}
                    />
                </div>
            </Seccion>

            <Seccion titulo="Facturación y crédito">
                <RadioGroupField
                    name="tipo_cliente"
                    etiqueta="Tipo de cliente"
                    defaultValue={cliente?.tipo_cliente ?? 'ELECTRONICO'}
                    opciones={TIPOS_CLIENTE}
                    error={errors.tipo_cliente}
                />
                <RadioGroupField
                    name="retenedor_fuente"
                    etiqueta="Retenedor en la fuente"
                    defaultValue={cliente?.retenedor_fuente ?? 'NO'}
                    opciones={RETENEDOR_FUENTE}
                    error={errors.retenedor_fuente}
                />
                <TextField
                    name="limite_credito"
                    etiqueta="Límite de crédito"
                    type="number"
                    min={0}
                    step="0.01"
                    defaultValue={cliente?.limite_credito ?? 0}
                    error={errors.limite_credito}
                />
                <div className="flex items-center gap-2">
                    <Checkbox
                        id="campo-activo"
                        name="activo"
                        defaultChecked={cliente?.activo ?? true}
                    />
                    <Label htmlFor="campo-activo">Cliente activo</Label>
                </div>
            </Seccion>

            <Seccion titulo="Documentos y notas">
                <div className="grid gap-2">
                    <Label htmlFor="campo-rut">
                        Archivo del RUT (opcional)
                    </Label>
                    <Input
                        id="campo-rut"
                        type="file"
                        name="rut"
                        accept=".pdf,image/*"
                    />
                    <p className="text-sm text-muted-foreground">
                        PDF o imagen. Máximo 4 MB.
                    </p>
                    <InputError message={errors.rut} />
                </div>
                {cliente?.rut_url && (
                    <div className="flex flex-wrap items-center gap-4 text-sm">
                        <a
                            href={cliente.rut_url}
                            target="_blank"
                            rel="noreferrer"
                            className="underline underline-offset-4"
                        >
                            Ver RUT actual
                        </a>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="campo-quitar-rut"
                                name="quitar_rut"
                                checked={quitarRut}
                                onCheckedChange={(valor) =>
                                    setQuitarRut(valor === true)
                                }
                            />
                            <Label htmlFor="campo-quitar-rut">
                                Quitar RUT actual
                            </Label>
                        </div>
                    </div>
                )}
                <TextField
                    name="novedad"
                    etiqueta="Novedad (opcional)"
                    defaultValue={cliente?.novedad ?? ''}
                    ayuda="Una observación que conviene tener presente con este cliente."
                    error={errors.novedad}
                />
            </Seccion>
        </div>
    );
}
