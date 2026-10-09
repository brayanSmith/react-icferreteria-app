import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

/** Colores sugeridos para elegir con un clic. */
const SUGERIDOS = [
    '#2563eb',
    '#0891b2',
    '#059669',
    '#65a30d',
    '#d97706',
    '#dc2626',
    '#db2777',
    '#7c3aed',
];

type ColorFieldProps = {
    /** Nombre del campo enviado al servidor. */
    name: string;
    etiqueta: string;
    ayuda: string;
    /** Color guardado (#rrggbb); null mientras se usa el predeterminado. */
    inicial: string | null;
    error?: string;
};

/**
 * Selector de un color de la empresa. Sin color elegido se envía vacío y la
 * aplicación conserva su color predeterminado.
 */
export default function ColorField({
    name,
    etiqueta,
    ayuda,
    inicial,
    error,
}: ColorFieldProps) {
    const [color, setColor] = useState<string | null>(inicial);

    return (
        <div className="grid content-start gap-3">
            <Label htmlFor={name}>{etiqueta}</Label>

            <input type="hidden" name={name} value={color ?? ''} />

            <div className="flex flex-wrap items-center gap-3">
                <input
                    id={name}
                    type="color"
                    value={color ?? '#171717'}
                    onChange={(evento) => setColor(evento.target.value)}
                    className="h-9 w-12 cursor-pointer rounded-md border border-input bg-transparent p-1"
                />

                {SUGERIDOS.map((sugerido) => (
                    <button
                        key={sugerido}
                        type="button"
                        aria-label={`Usar ${sugerido}`}
                        onClick={() => setColor(sugerido)}
                        style={{ backgroundColor: sugerido }}
                        className={`size-6 rounded-full border-2 ${
                            color === sugerido
                                ? 'border-foreground'
                                : 'border-transparent'
                        }`}
                    />
                ))}

                {color !== null && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => setColor(null)}
                    >
                        Restablecer
                    </Button>
                )}
            </div>

            <p className="text-sm text-muted-foreground">
                {color === null ? 'Predeterminado.' : ayuda}
            </p>
            <InputError message={error} />
        </div>
    );
}
