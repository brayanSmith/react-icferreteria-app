import { useState } from 'react';
import InputError from '@/components/input-error';
import SelectField from '@/components/crud/select-field';
import TextField from '@/components/crud/text-field';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    CATEGORIAS_PROVEEDOR,
    TIPOS_CUENTA,
    TIPOS_PROVEEDOR,
} from '@/lib/catalogos';
import type { Proveedor } from '@/types';

/** Título de cada bloque del formulario. */
function Seccion({
    titulo,
    children,
}: {
    titulo: string;
    children: React.ReactNode;
}) {
    return (
        <fieldset className="space-y-4 rounded-lg border p-4">
            <legend className="px-2 text-sm font-medium">{titulo}</legend>
            {children}
        </fieldset>
    );
}

/**
 * Campos del formulario de proveedor, agrupados por tema. Los usan los modales
 * de crear y editar.
 */
export default function ProveedorFields({
    proveedor,
    errors,
}: {
    proveedor?: Proveedor;
    errors: Record<string, string>;
}) {
    // El valor del flete solo se habilita si el proveedor cobra flete.
    const [cobraFlete, setCobraFlete] = useState(proveedor?.flete ?? false);
    const [quitarRut, setQuitarRut] = useState(false);

    return (
        <div className="space-y-4">
            <Seccion titulo="Datos generales">
                <TextField
                    name="nombre_proveedor"
                    etiqueta="Nombre"
                    defaultValue={proveedor?.nombre_proveedor}
                    required
                    error={errors.nombre_proveedor}
                />
                <TextField
                    name="razon_social_proveedor"
                    etiqueta="Razón social (opcional)"
                    defaultValue={proveedor?.razon_social_proveedor ?? ''}
                    error={errors.razon_social_proveedor}
                />
                <div className="grid gap-4 sm:grid-cols-3">
                    <TextField
                        name="nit_proveedor"
                        etiqueta="NIT"
                        defaultValue={proveedor?.nit_proveedor}
                        required
                        error={errors.nit_proveedor}
                    />
                    <SelectField
                        name="tipo_proveedor"
                        etiqueta="Tipo"
                        defaultValue={
                            proveedor?.tipo_proveedor ?? 'REMISIONADO'
                        }
                        opciones={TIPOS_PROVEEDOR}
                        error={errors.tipo_proveedor}
                    />
                    <SelectField
                        name="categoria_proveedor"
                        etiqueta="Categoría"
                        defaultValue={
                            proveedor?.categoria_proveedor ?? 'NO_DECLARANTE'
                        }
                        opciones={CATEGORIAS_PROVEEDOR}
                        error={errors.categoria_proveedor}
                    />
                </div>
            </Seccion>

            <Seccion titulo="Contacto y ubicación">
                <div className="grid gap-4 sm:grid-cols-2">
                    <TextField
                        name="departamento_proveedor"
                        etiqueta="Departamento"
                        defaultValue={proveedor?.departamento_proveedor ?? ''}
                        error={errors.departamento_proveedor}
                    />
                    <TextField
                        name="ciudad_proveedor"
                        etiqueta="Ciudad"
                        defaultValue={proveedor?.ciudad_proveedor ?? ''}
                        error={errors.ciudad_proveedor}
                    />
                    <TextField
                        name="direccion_proveedor"
                        etiqueta="Dirección"
                        defaultValue={proveedor?.direccion_proveedor ?? ''}
                        error={errors.direccion_proveedor}
                    />
                    <TextField
                        name="telefono_proveedor"
                        etiqueta="Teléfono"
                        defaultValue={proveedor?.telefono_proveedor ?? ''}
                        error={errors.telefono_proveedor}
                    />
                </div>
            </Seccion>

            <Seccion titulo="Datos bancarios">
                <div className="grid gap-4 sm:grid-cols-3">
                    <TextField
                        name="banco_proveedor"
                        etiqueta="Banco"
                        defaultValue={proveedor?.banco_proveedor ?? ''}
                        error={errors.banco_proveedor}
                    />
                    <SelectField
                        name="tipo_cuenta_proveedor"
                        etiqueta="Tipo de cuenta"
                        defaultValue={proveedor?.tipo_cuenta_proveedor ?? ''}
                        opciones={TIPOS_CUENTA}
                        placeholder="Sin definir"
                        error={errors.tipo_cuenta_proveedor}
                    />
                    <TextField
                        name="numero_cuenta_proveedor"
                        etiqueta="Número de cuenta"
                        defaultValue={proveedor?.numero_cuenta_proveedor ?? ''}
                        error={errors.numero_cuenta_proveedor}
                    />
                </div>
            </Seccion>

            <Seccion titulo="Condiciones comerciales">
                <div className="grid gap-4 sm:grid-cols-3">
                    <TextField
                        name="convenio"
                        etiqueta="Convenio"
                        defaultValue={proveedor?.convenio ?? ''}
                        error={errors.convenio}
                    />
                    <TextField
                        name="tiempo_respuesta"
                        etiqueta="Tiempo de respuesta"
                        defaultValue={proveedor?.tiempo_respuesta ?? ''}
                        error={errors.tiempo_respuesta}
                    />
                    <TextField
                        name="fabricante"
                        etiqueta="Fabricante"
                        defaultValue={proveedor?.fabricante ?? ''}
                        error={errors.fabricante}
                    />
                </div>
                <div className="grid items-end gap-4 sm:grid-cols-2">
                    <div className="flex items-center gap-2 sm:h-9">
                        <Checkbox
                            id="campo-flete"
                            name="flete"
                            checked={cobraFlete}
                            onCheckedChange={(valor) =>
                                setCobraFlete(valor === true)
                            }
                        />
                        <Label htmlFor="campo-flete">Cobra flete</Label>
                    </div>
                    <TextField
                        name="valor_flete"
                        etiqueta="Valor del flete"
                        type="number"
                        min={0}
                        step="0.01"
                        defaultValue={proveedor?.valor_flete ?? 0}
                        disabled={!cobraFlete}
                        error={errors.valor_flete}
                    />
                </div>
            </Seccion>

            <Seccion titulo="RUT">
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
                {proveedor?.rut_url && (
                    <div className="flex flex-wrap items-center gap-4 text-sm">
                        <a
                            href={proveedor.rut_url}
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
            </Seccion>
        </div>
    );
}
