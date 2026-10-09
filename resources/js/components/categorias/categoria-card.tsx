import { ChevronDown, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import CategoriaController from '@/actions/App/Http/Controllers/Gestion/CategoriaController';
import SubCategoriaController from '@/actions/App/Http/Controllers/Gestion/SubCategoriaController';
import ConfirmDeleteDialog from '@/components/crud/confirm-delete-dialog';
import FormDialog from '@/components/crud/form-dialog';
import TextField from '@/components/crud/text-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import type { Categoria, SubCategoria } from '@/types';

/** Campo del formulario de categoría. */
export function CamposCategoria({
    categoria,
    errors,
}: {
    categoria?: Categoria;
    errors: Record<string, string>;
}) {
    return (
        <TextField
            name="nombre_categoria"
            etiqueta="Nombre de la categoría"
            defaultValue={categoria?.nombre_categoria}
            placeholder="Herramientas"
            required
            error={errors.nombre_categoria}
        />
    );
}

/** Campo del formulario de subcategoría. */
function CamposSubCategoria({
    subCategoria,
    errors,
}: {
    subCategoria?: SubCategoria;
    errors: Record<string, string>;
}) {
    return (
        <TextField
            name="nombre_sub_categoria"
            etiqueta="Nombre de la subcategoría"
            defaultValue={subCategoria?.nombre_sub_categoria}
            placeholder="Herramientas manuales"
            required
            error={errors.nombre_sub_categoria}
        />
    );
}

/**
 * Una categoría con sus subcategorías desplegables. Todo se administra aquí:
 * editar y eliminar la categoría, y crear, editar y eliminar sus subcategorías.
 */
export default function CategoriaCard({ categoria }: { categoria: Categoria }) {
    const can = useCan();
    const [abierta, setAbierta] = useState(false);

    return (
        <div className="rounded-lg border">
            <div className="flex flex-wrap items-center justify-between gap-3 p-4">
                <button
                    type="button"
                    onClick={() => setAbierta((actual) => !actual)}
                    className="flex items-center gap-2 text-left"
                    aria-expanded={abierta}
                >
                    {abierta ? (
                        <ChevronDown className="size-4" />
                    ) : (
                        <ChevronRight className="size-4" />
                    )}
                    <span className="font-medium">
                        {categoria.nombre_categoria}
                    </span>
                    <Badge variant="secondary">
                        {categoria.sub_categorias.length} subcategorías
                    </Badge>
                    <span className="text-sm text-muted-foreground">
                        {categoria.productos_count ?? 0} productos
                    </span>
                </button>

                <div className="flex gap-2">
                    {can('categorias.editar') && (
                        <FormDialog
                            titulo={`Editar ${categoria.nombre_categoria}`}
                            accion={CategoriaController.update.form(
                                categoria.id,
                            )}
                            etiquetaGuardar="Guardar cambios"
                            trigger={
                                <Button size="sm" variant="outline">
                                    Editar
                                </Button>
                            }
                        >
                            {({ errors }) => (
                                <CamposCategoria
                                    categoria={categoria}
                                    errors={errors}
                                />
                            )}
                        </FormDialog>
                    )}
                    {can('categorias.eliminar') && (
                        <ConfirmDeleteDialog
                            titulo={`¿Eliminar la categoría ${categoria.nombre_categoria}?`}
                            descripcion="Se eliminan también sus subcategorías. Solo se puede si ningún producto la usa."
                            accion={CategoriaController.destroy.form(
                                categoria.id,
                            )}
                        />
                    )}
                </div>
            </div>

            {abierta && (
                <div className="space-y-3 border-t bg-muted/20 p-4">
                    {categoria.sub_categorias.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Esta categoría todavía no tiene subcategorías.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-lg border bg-background">
                            {categoria.sub_categorias.map((sub) => (
                                <li
                                    key={sub.id}
                                    className="flex flex-wrap items-center justify-between gap-2 px-4 py-2"
                                >
                                    <span>
                                        {sub.nombre_sub_categoria}
                                        <span className="ml-2 text-sm text-muted-foreground">
                                            {sub.productos_count ?? 0} productos
                                        </span>
                                    </span>
                                    <div className="flex gap-2">
                                        {can('categorias.editar') && (
                                            <FormDialog
                                                titulo={`Editar ${sub.nombre_sub_categoria}`}
                                                accion={SubCategoriaController.update.form(
                                                    {
                                                        categoria: categoria.id,
                                                        sub_categoria: sub.id,
                                                    },
                                                )}
                                                etiquetaGuardar="Guardar cambios"
                                                trigger={
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        Editar
                                                    </Button>
                                                }
                                            >
                                                {({ errors }) => (
                                                    <CamposSubCategoria
                                                        subCategoria={sub}
                                                        errors={errors}
                                                    />
                                                )}
                                            </FormDialog>
                                        )}
                                        {can('categorias.eliminar') && (
                                            <ConfirmDeleteDialog
                                                titulo={`¿Eliminar la subcategoría ${sub.nombre_sub_categoria}?`}
                                                descripcion="Solo se puede eliminar si ningún producto la usa."
                                                accion={SubCategoriaController.destroy.form(
                                                    {
                                                        categoria: categoria.id,
                                                        sub_categoria: sub.id,
                                                    },
                                                )}
                                            />
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}

                    {can('categorias.crear') && (
                        <FormDialog
                            titulo={`Nueva subcategoría de ${categoria.nombre_categoria}`}
                            accion={SubCategoriaController.store.form(
                                categoria.id,
                            )}
                            etiquetaGuardar="Crear subcategoría"
                            trigger={
                                <Button size="sm" variant="outline">
                                    Agregar subcategoría
                                </Button>
                            }
                        >
                            {({ errors }) => (
                                <CamposSubCategoria errors={errors} />
                            )}
                        </FormDialog>
                    )}
                </div>
            )}
        </div>
    );
}
