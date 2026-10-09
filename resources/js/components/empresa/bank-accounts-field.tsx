import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { CuentaBancaria } from '@/types';

type CuentaConClave = CuentaBancaria & { clave: number };

const CUENTA_VACIA: CuentaBancaria = {
    banco: '',
    tipo: 'AHORRO',
    numero: '',
    titular: '',
};

/**
 * Lista repetible de cuentas bancarias. Los campos se envían como
 * `cuentas_bancarias[i][campo]`; cada fila conserva una clave estable para
 * que quitar una no mezcle lo escrito en las demás.
 */
export default function BankAccountsField({
    inicial,
    errors,
}: {
    inicial: CuentaBancaria[];
    errors: Record<string, string>;
}) {
    const [cuentas, setCuentas] = useState<CuentaConClave[]>(
        inicial.map((cuenta, posicion) => ({ ...cuenta, clave: posicion })),
    );
    const [siguienteClave, setSiguienteClave] = useState(inicial.length);

    function agregar() {
        setCuentas((actuales) => [
            ...actuales,
            { ...CUENTA_VACIA, clave: siguienteClave },
        ]);
        setSiguienteClave((actual) => actual + 1);
    }

    function quitar(clave: number) {
        setCuentas((actuales) =>
            actuales.filter((cuenta) => cuenta.clave !== clave),
        );
    }

    return (
        <div className="space-y-4">
            {cuentas.length === 0 && (
                <p className="rounded-lg border p-4 text-sm text-muted-foreground">
                    No hay cuentas registradas.
                </p>
            )}

            {cuentas.map((cuenta, posicion) => {
                const nombre = (campo: string) =>
                    `cuentas_bancarias[${posicion}][${campo}]`;
                const error = (campo: string) =>
                    errors[`cuentas_bancarias.${posicion}.${campo}`];

                return (
                    <div
                        key={cuenta.clave}
                        className="grid gap-4 rounded-lg border p-4 sm:grid-cols-2"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor={`cuenta-banco-${cuenta.clave}`}>
                                Banco
                            </Label>
                            <Input
                                id={`cuenta-banco-${cuenta.clave}`}
                                name={nombre('banco')}
                                defaultValue={cuenta.banco}
                                required
                            />
                            <InputError message={error('banco')} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`cuenta-tipo-${cuenta.clave}`}>
                                Tipo de cuenta
                            </Label>
                            <select
                                id={`cuenta-tipo-${cuenta.clave}`}
                                name={nombre('tipo')}
                                defaultValue={cuenta.tipo}
                                className={cn(
                                    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none',
                                    'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                )}
                            >
                                <option value="AHORRO">Ahorros</option>
                                <option value="CORRIENTE">Corriente</option>
                            </select>
                            <InputError message={error('tipo')} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`cuenta-numero-${cuenta.clave}`}>
                                Número de cuenta
                            </Label>
                            <Input
                                id={`cuenta-numero-${cuenta.clave}`}
                                name={nombre('numero')}
                                defaultValue={cuenta.numero}
                                required
                            />
                            <InputError message={error('numero')} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`cuenta-titular-${cuenta.clave}`}>
                                Titular (opcional)
                            </Label>
                            <Input
                                id={`cuenta-titular-${cuenta.clave}`}
                                name={nombre('titular')}
                                defaultValue={cuenta.titular ?? ''}
                            />
                            <InputError message={error('titular')} />
                        </div>

                        <div className="sm:col-span-2">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => quitar(cuenta.clave)}
                            >
                                Quitar esta cuenta
                            </Button>
                        </div>
                    </div>
                );
            })}

            <Button type="button" variant="outline" onClick={agregar}>
                Agregar cuenta bancaria
            </Button>
            <InputError message={errors.cuentas_bancarias} />
        </div>
    );
}
