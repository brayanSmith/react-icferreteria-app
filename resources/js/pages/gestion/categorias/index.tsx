import { Head } from '@inertiajs/react';
import CategoriaController from '@/actions/App/Http/Controllers/Gestion/CategoriaController';
import CategoriaCard, {
    CamposCategoria,
} from '@/components/categorias/categoria-card';
import ExportarMenu from '@/components/crud/exportar-menu';
import FormDialog from '@/components/crud/form-dialog';
import ImportarDialog from '@/components/crud/importar-dialog';
import ImportacionesPanel from '@/components/crud/importaciones-panel';
import PageHeader from '@/components/crud/page-header';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { index } from '@/routes/gestion/categorias';
import type { Categoria, Importacion, ImportacionPendiente } from '@/types';

/**
 * Categorías de productos y sus subcategorías, en un solo módulo: cada
 * categoría se despliega para ver y administrar sus subcategorías.
 */
export default function CategoriasIndex({
    categorias,
    importaciones,
    importacionPendiente,
}: {
    categorias: { data: Categoria[] };
    importaciones: { data: Importacion[] };
    importacionPendiente: ImportacionPendiente | null;
}) {
    const can = useCan();

    return (
        <>
            <Head title="Categorías" />

            <div className="space-y-6 p-4">
                <PageHeader
                    titulo="Categorías"
                    descripcion="Organiza tus productos en categorías y subcategorías"
                    accion={
                        <div className="flex flex-wrap gap-2">
                            <ExportarMenu modulo="categorias" />
                            {can('categorias.crear') && (
                                <>
                                    <ImportarDialog
                                        tipos={[
                                            {
                                                valor: 'categorias',
                                                etiqueta: 'Categorías',
                                            },
                                            {
                                                valor: 'subcategorias',
                                                etiqueta: 'Subcategorías',
                                            },
                                        ]}
                                        pendiente={importacionPendiente}
                                    />
                                    <FormDialog
                                        titulo="Nueva categoría"
                                        accion={CategoriaController.store.form()}
                                        etiquetaGuardar="Crear categoría"
                                        trigger={
                                            <Button>Nueva categoría</Button>
                                        }
                                    >
                                        {({ errors }) => (
                                            <CamposCategoria errors={errors} />
                                        )}
                                    </FormDialog>
                                </>
                            )}
                        </div>
                    }
                />

                <ImportacionesPanel importaciones={importaciones.data} />

                {categorias.data.length === 0 ? (
                    <p className="rounded-lg border p-8 text-center text-sm text-muted-foreground">
                        Todavía no hay categorías. Crea la primera para poder
                        registrar productos.
                    </p>
                ) : (
                    <div className="space-y-3">
                        {categorias.data.map((categoria) => (
                            <CategoriaCard
                                key={categoria.id}
                                categoria={categoria}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

CategoriasIndex.layout = {
    breadcrumbs: [{ title: 'Categorías', href: index() }],
};
