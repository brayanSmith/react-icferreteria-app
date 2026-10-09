import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { exportar } from '@/routes/gestion';

const FORMATOS = [
    { valor: 'xlsx', etiqueta: 'Excel (.xlsx)' },
    { valor: 'csv', etiqueta: 'CSV (.csv)' },
    { valor: 'txt', etiqueta: 'Texto (.txt)' },
    { valor: 'pdf', etiqueta: 'PDF (.pdf)' },
];

/**
 * Menú para descargar un módulo en CSV, TXT, XLSX o PDF. Cada opción es un
 * enlace de descarga directa; el servidor arma el archivo.
 */
export default function ExportarMenu({ modulo }: { modulo: string }) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline">
                    <Download />
                    Exportar
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {FORMATOS.map((formato) => (
                    <DropdownMenuItem key={formato.valor} asChild>
                        <a
                            href={
                                exportar({ modulo, formato: formato.valor }).url
                            }
                        >
                            {formato.etiqueta}
                        </a>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
