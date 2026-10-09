import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordGeneratorField from '@/components/password-generator-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TenantAdminValores } from '@/types';

type UserCredentialsFieldsProps = {
    errors?: Record<string, string>;
    /** Valores con los que arranca el formulario (al editar o retomar un borrador). */
    valoresIniciales?: Partial<TenantAdminValores>;
    /** La contraseña puede quedar vacía (al editar se conserva la actual). */
    passwordOpcional?: boolean;
    /** Avisa cada vez que cambia un campo; sirve para formularios sin <form>. */
    onChange?: (valores: TenantAdminValores) => void;
};

/**
 * Credenciales de un usuario (nombre, correo, documento y contraseña temporal). Los inputs llevan `name`, así que
 * también funcionan dentro de un <Form> de Inertia.
 */
export default function UserCredentialsFields({
    errors = {},
    valoresIniciales,
    passwordOpcional = false,
    onChange,
}: UserCredentialsFieldsProps) {
    const [valores, setValores] = useState<TenantAdminValores>({
        name: valoresIniciales?.name ?? '',
        email: valoresIniciales?.email ?? '',
        n_documento: valoresIniciales?.n_documento ?? '',
        password: valoresIniciales?.password ?? '',
    });

    function cambiar(campo: keyof TenantAdminValores, valor: string) {
        const nuevos = { ...valores, [campo]: valor };

        setValores(nuevos);
        onChange?.(nuevos);
    }

    return (
        <div className="space-y-6">
            <div className="grid gap-2">
                <Label htmlFor="user-name">Nombre completo</Label>
                <Input
                    id="user-name"
                    name="name"
                    value={valores.name}
                    onChange={(event) => cambiar('name', event.target.value)}
                    required
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="user-email">Correo electrónico</Label>
                    <Input
                        id="user-email"
                        type="email"
                        name="email"
                        value={valores.email}
                        onChange={(event) =>
                            cambiar('email', event.target.value)
                        }
                        required
                        autoComplete="off"
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="user-n-documento">
                        Número de documento
                    </Label>
                    <Input
                        id="user-n-documento"
                        name="n_documento"
                        value={valores.n_documento}
                        onChange={(event) =>
                            cambiar('n_documento', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.n_documento} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="user-password">
                    {passwordOpcional
                        ? 'Nueva contraseña temporal (opcional)'
                        : 'Contraseña temporal'}
                </Label>
                <PasswordGeneratorField
                    id="user-password"
                    name="password"
                    value={valores.password}
                    onChange={(valor) => cambiar('password', valor)}
                    required={!passwordOpcional}
                />
                <p className="text-sm text-muted-foreground">
                    {passwordOpcional
                        ? 'Déjala vacía para conservar la actual. Si escribes una, deberá cambiarla en su próximo ingreso.'
                        : 'Copia la contraseña y entrégasela a la persona: deberá cambiarla en su primer ingreso.'}
                </p>
                <InputError message={errors.password} />
            </div>
        </div>
    );
}
