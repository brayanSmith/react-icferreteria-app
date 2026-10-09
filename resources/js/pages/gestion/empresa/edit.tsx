import { Head } from '@inertiajs/react';
import EmpresaCard from '@/components/empresa/empresa-card';
import EmpresaDialog from '@/components/empresa/empresa-dialog';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useCan } from '@/hooks/use-can';
import { edit } from '@/routes/gestion/empresa';
import type { Empresa } from '@/types';

type Props = {
    empresa: { data: Empresa } | null;
    nombreSugerido: string;
};

export default function EmpresaEdit({ empresa, nombreSugerido }: Props) {
    const can = useCan();
    const datos = empresa?.data ?? null;

    return (
        <>
            <Head title="Mi empresa" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Mi empresa"
                        description="Datos que se muestran en tus documentos y en la aplicación"
                    />
                    {datos !== null && can('empresa.editar') && (
                        <EmpresaDialog
                            empresa={datos}
                            nombreSugerido={nombreSugerido}
                        >
                            <Button data-test="editar-empresa">
                                Editar empresa
                            </Button>
                        </EmpresaDialog>
                    )}
                </div>

                {datos !== null ? (
                    <EmpresaCard empresa={datos} />
                ) : (
                    <Card className="max-w-3xl">
                        <CardContent className="flex flex-col items-center gap-4 py-12 text-center">
                            <p className="text-muted-foreground">
                                Aún no has registrado los datos de tu empresa.
                            </p>
                            {can('empresa.editar') && (
                                <EmpresaDialog
                                    empresa={null}
                                    nombreSugerido={nombreSugerido}
                                >
                                    <Button data-test="agregar-empresa">
                                        Agregar empresa
                                    </Button>
                                </EmpresaDialog>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

EmpresaEdit.layout = {
    breadcrumbs: [{ title: 'Mi empresa', href: edit() }],
};
