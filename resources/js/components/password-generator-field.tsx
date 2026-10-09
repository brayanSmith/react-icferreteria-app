import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useClipboard } from '@/hooks/use-clipboard';
import { generatePassword } from '@/lib/generate-password';

/**
 * Campo de contraseña visible con botones para generarla y copiarla. Pensado
 * para contraseñas temporales que se entregan a otra persona.
 */
export default function PasswordGeneratorField({
    id,
    name,
    value,
    onChange,
    required = true,
}: {
    id: string;
    name: string;
    value: string;
    onChange: (valor: string) => void;
    required?: boolean;
}) {
    const [copiado, copiar] = useClipboard();

    return (
        <div className="flex flex-wrap gap-2">
            <Input
                id={id}
                name={name}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                required={required}
                autoComplete="off"
                spellCheck={false}
                className="min-w-0 flex-1 font-mono"
            />
            <Button
                type="button"
                variant="outline"
                onClick={() => onChange(generatePassword())}
            >
                Generar
            </Button>
            <Button
                type="button"
                variant="outline"
                disabled={value === ''}
                onClick={() => copiar(value)}
            >
                {copiado !== null && copiado === value ? 'Copiada' : 'Copiar'}
            </Button>
        </div>
    );
}
