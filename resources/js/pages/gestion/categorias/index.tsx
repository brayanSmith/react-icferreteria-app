import { Head } from '@inertiajs/react';
import CategoriaController from '@/actions/App/Http/Controllers/Gestion/CategoriaController';
import CategoriaCard, {
    CamposCategoria,
} from '@/components/categorias/categoria-card';
import FormDialog from '@/components/crud/form-dialog';
import PageHeader from '@/components/crud/page-header';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { index } from '@/routes/gestion/categorias';
import type { Categoria } from '@/types';

/**
 * Categorías de productos y sus subcategorías, en un solo módulo: cada
 * categoría se despliega para ver y administrar sus subcategorías.
 */
export default function CategoriasIndex({
    categorias,
}: {
    categorias: { data: Categoria[] };
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
                        can('categorias.crear') && (
                            <FormDialog
                                titulo="Nueva categoría"
                                accion={CategoriaController.store.form()}
                                etiquetaGuardar="Crear categoría"
                                trigger={<Button>Nueva categoría</Button>}
                            >
                                {({ errors }) => (
                                    <CamposCategoria errors={errors} />
                                )}
                            </FormDialog>
                        )
                    }
                />

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
