import { Form, router } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { useState } from 'react';
import ImportacionController from '@/actions/App/Http/Controllers/Gestion/ImportacionController';
import RadioGroupField from '@/components/crud/radio-group-field';
import SelectField from '@/components/crud/select-field';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { plantilla } from '@/routes/gestion/importar';
import type { ImportacionPendiente } from '@/types';

const NOMBRES_DELIMITADOR: Record<string, string> = {
    ',': 'coma (,)',
    ';': 'punto y coma (;)',
    '\t': 'tabulador',
    '|': 'barra vertical (|)',
};

type Modo = 'crear' | 'actualizar' | 'ambos';

const MODOS: { valor: Modo; etiqueta: string }[] = [
    { valor: 'crear', etiqueta: 'Solo crear' },
    { valor: 'actualizar', etiqueta: 'Solo actualizar' },
    { valor: 'ambos', etiqueta: 'Crear y actualizar' },
];

const DESCRIPCION_MODO: Record<Modo, string> = {
    crear: 'Cada fila crea un registro nuevo. Lo que ya existe se omite.',
    actualizar:
        'Cada fila modifica un registro que ya existe, identificado por su ID. Las filas cuyo ID no existe se rechazan.',
    ambos: 'La fila que trae ID actualiza ese registro; la que no trae ID crea uno nuevo.',
};

/**
 * Paso 2: el archivo ya está subido y el sistema detectó sus columnas. Aquí el
 * usuario dice a qué columna del sistema corresponde cada una (ya vienen
 * sugeridas por nombre) y si el archivo crea registros nuevos o actualiza los
 * existentes. Al confirmar, la importación sigue en segundo plano.
 */
function ConfigurarImportacion({
    pendiente,
}: {
    pendiente: ImportacionPendiente;
}) {
    const [modo, setModo] = useState<Modo>('crear');
    const necesitaId = modo !== 'crear';
    const [seleccion, setSeleccion] = useState<Record<string, string>>(() =>
        Object.fromEntries(
            pendiente.columnas.map((columna) => {
                const posicion = pendiente.sugerencia[columna.clave];

                return [
                    columna.clave,
                    posicion === null ? '' : String(posicion),
                ];
            }),
        ),
    );

    const opciones = pendiente.encabezados.map((encabezado, posicion) => ({
        valor: posicion,
        etiqueta: encabezado === '' ? `Columna ${posicion + 1}` : encabezado,
    }));

    function cancelar() {
        router.delete(
            ImportacionController.destroy.url({ importacion: pendiente.id }),
            { preserveScroll: true },
        );
    }

    return (
        <Dialog open onOpenChange={(abierto) => !abierto && cancelar()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        Relacionar las columnas de{' '}
                        {pendiente.etiqueta.toLowerCase()}
                    </DialogTitle>
                    <DialogDescription>
                        Archivo {pendiente.nombre_original}
                        {pendiente.delimitador &&
                            `, separado por ${NOMBRES_DELIMITADOR[pendiente.delimitador] ?? pendiente.delimitador}`}
                        . Elige qué columna de tu archivo corresponde a cada
                        dato del sistema.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...ImportacionController.confirmar.form({
                        importacion: pendiente.id,
                    })}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="space-y-2">
                                <RadioGroupField
                                    name="modo"
                                    etiqueta="¿Qué hace este archivo?"
                                    opciones={MODOS}
                                    columnas={3}
                                    defaultValue={modo}
                                    onValueChange={(valor) =>
                                        setModo(valor as Modo)
                                    }
                                    error={errors.modo}
                                />
                                <p className="text-sm text-muted-foreground">
                                    {DESCRIPCION_MODO[modo]}
                                </p>
                            </div>

                            <div className="space-y-4">
                                {pendiente.columnas.map((columna) => {
                                    const requerida = necesitaId
                                        ? columna.requerida_actualizar
                                        : columna.requerida_crear;
                                    const posicion = seleccion[columna.clave];
                                    const ejemplo =
                                        posicion === ''
                                            ? null
                                            : pendiente.muestra[0]?.[
                                                  Number(posicion)
                                              ];

                                    return (
                                        <div
                                            key={columna.clave}
                                            className="grid gap-1"
                                        >
                                            <SelectField
                                                name={`mapeo[${columna.clave}]`}
                                                etiqueta={`${columna.etiqueta}${requerida ? ' *' : ''}`}
                                                opciones={opciones}
                                                placeholder="No importar esta columna"
                                                value={posicion}
                                                onValueChange={(valor) =>
                                                    setSeleccion((actual) => ({
                                                        ...actual,
                                                        [columna.clave]: valor,
                                                    }))
                                                }
                                                error={
                                                    errors[
                                                        `mapeo.${columna.clave}`
                                                    ]
                                                }
                                            />
                                            {ejemplo && (
                                                <p className="text-xs text-muted-foreground">
                                                    Ejemplo: {ejemplo}
                                                </p>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>

                            <DialogFooter className="gap-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={cancelar}
                                >
                                    Cancelar
                                </Button>
                                <Button disabled={processing}>
                                    Iniciar importación
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

/**
 * Importar un CSV en dos pasos. Paso 1 (este botón): subir el archivo; el
 * sistema detecta su separador (`,` o `;`), su codificación y sus columnas.
 * Paso 2 (ConfigurarImportacion): relacionar las columnas y confirmar.
 */
export default function ImportarDialog({
    tipos,
    pendiente,
}: {
    /** Qué se puede importar desde esta pantalla; con varias, el usuario elige con un radio. */
    tipos: { valor: string; etiqueta: string }[];
    /** Archivo ya subido que espera que se relacionen sus columnas. */
    pendiente: ImportacionPendiente | null;
}) {
    const [abierto, setAbierto] = useState(false);
    const [modulo, setModulo] = useState(tipos[0].valor);

    return (
        <>
            <Dialog open={abierto} onOpenChange={setAbierto}>
                <DialogTrigger asChild>
                    <Button variant="outline">
                        <Upload />
                        Importar
                    </Button>
                </DialogTrigger>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Importar datos</DialogTitle>
                        <DialogDescription>
                            Sube un archivo CSV separado por comas o por punto y
                            coma. En el siguiente paso relacionas sus columnas
                            con las del sistema.
                        </DialogDescription>
                    </DialogHeader>

                    <Form
                        {...ImportacionController.store.form({ modulo })}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setAbierto(false)}
                        className="space-y-6"
                    >
                        {({ errors, processing }) => (
                            <>
                                {tipos.length > 1 && (
                                    <RadioGroupField
                                        name="tipo_importacion"
                                        etiqueta="¿Qué quieres importar?"
                                        opciones={tipos}
                                        defaultValue={modulo}
                                        onValueChange={setModulo}
                                    />
                                )}
                                <div className="grid gap-2">
                                    <Label htmlFor="campo-archivo">
                                        Archivo CSV
                                    </Label>
                                    <Input
                                        id="campo-archivo"
                                        type="file"
                                        name="archivo"
                                        accept=".csv,.txt,text/csv"
                                        required
                                    />
                                    <InputError message={errors.archivo} />
                                    <a
                                        href={plantilla({ modulo }).url}
                                        className="text-sm underline underline-offset-4"
                                    >
                                        Descargar la plantilla de ejemplo
                                    </a>
                                </div>

                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                        >
                                            Cancelar
                                        </Button>
                                    </DialogClose>
                                    <Button disabled={processing}>
                                        Subir y continuar
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>

            {pendiente && (
                <ConfigurarImportacion
                    key={pendiente.id}
                    pendiente={pendiente}
                />
            )}
        </>
    );
}
