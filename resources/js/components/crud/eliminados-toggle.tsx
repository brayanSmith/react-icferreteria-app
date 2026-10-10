import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

type EliminadosToggleProps = {
    id: string;
    etiqueta: string;
    activo: boolean;
    onCambiar: (activo: boolean) => void;
};

/**
 * Casillero para ver solo los registros eliminados. Se pasa como `barra` de
 * `CrudTable`; la página decide qué filas mostrar según su estado.
 */
export default function EliminadosToggle({
    id,
    etiqueta,
    activo,
    onCambiar,
}: EliminadosToggleProps) {
    return (
        <div className="flex items-center gap-2">
            <Checkbox
                id={id}
                checked={activo}
                onCheckedChange={(valor) => onCambiar(valor === true)}
            />
            <Label htmlFor={id}>{etiqueta}</Label>
        </div>
    );
}
