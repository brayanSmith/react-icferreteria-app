import { useEffect, useRef, useState } from 'react';
import type { ChangeEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { esImagenEditable } from '@/lib/image-editing';
import ImageCropDialog from './image-crop-dialog';
import type { AspectoRecorte } from './image-crop-dialog';

type ImageFieldProps = {
    id: string;
    /** Nombre del campo de archivo (`logo`, `icono`). */
    name: string;
    /** Nombre del campo que pide quitar la imagen actual. */
    nombreQuitar: string;
    etiqueta: string;
    ayuda: string;
    urlActual: string | null;
    error?: string;
    /** Proporciones de recorte disponibles; con una sola queda fija. */
    aspectos: AspectoRecorte[];
    /** Lado máximo, en píxeles, de la imagen que se sube. */
    maxLado: number;
};

/**
 * Campo de imagen con vista previa y edición: al elegir un archivo se abre un
 * modal para recortarlo y girarlo, y la imagen ya guardada también se puede
 * volver a editar. El resultado se coloca en el propio campo de archivo, así
 * viaja con el formulario como cualquier otro archivo.
 */
export default function ImageField({
    id,
    name,
    nombreQuitar,
    etiqueta,
    ayuda,
    urlActual,
    error,
    aspectos,
    maxLado,
}: ImageFieldProps) {
    const entradaRef = useRef<HTMLInputElement>(null);
    const [vistaPrevia, setVistaPrevia] = useState<string | null>(null);
    const [quitar, setQuitar] = useState(false);
    const [enEdicion, setEnEdicion] = useState<File | null>(null);
    // Archivo sin recortar, para poder volver a editarlo sin reelegirlo.
    const [original, setOriginal] = useState<File | null>(null);
    // Si se está reeditando, cancelar no debe descartar lo que ya había.
    const [reeditando, setReeditando] = useState(false);
    const [aviso, setAviso] = useState<string | null>(null);

    // Libera la URL temporal del archivo elegido cuando cambia o se desmonta.
    useEffect(() => {
        return () => {
            if (vistaPrevia) {
                URL.revokeObjectURL(vistaPrevia);
            }
        };
    }, [vistaPrevia]);

    function colocarArchivoEnCampo(archivo: File | null) {
        const entrada = entradaRef.current;

        if (!entrada) {
            return;
        }

        if (archivo === null) {
            entrada.value = '';

            return;
        }

        const transferencia = new DataTransfer();

        transferencia.items.add(archivo);
        entrada.files = transferencia.files;
    }

    function alElegirArchivo(evento: ChangeEvent<HTMLInputElement>) {
        const archivo = evento.target.files?.[0];

        setAviso(null);
        setQuitar(false);

        if (!archivo) {
            setVistaPrevia(null);
            setOriginal(null);

            return;
        }

        if (!esImagenEditable(archivo.type)) {
            // SVG, ICO o GIF: no se pueden recortar en el navegador; se suben tal cual.
            setVistaPrevia(URL.createObjectURL(archivo));
            setOriginal(null);

            return;
        }

        setOriginal(archivo);
        setReeditando(false);
        setEnEdicion(archivo);
    }

    async function editarImagenActual() {
        setAviso(null);

        // Reedita el archivo recién elegido; si no hay, descarga la imagen guardada.
        if (original) {
            setReeditando(true);
            setEnEdicion(original);

            return;
        }

        if (!urlActual) {
            return;
        }

        try {
            const respuesta = await fetch(urlActual);

            if (!respuesta.ok) {
                throw new Error('respuesta no válida');
            }

            const blob = await respuesta.blob();

            if (!esImagenEditable(blob.type)) {
                setAviso('Este formato de imagen no se puede recortar.');

                return;
            }

            const extension = blob.type.split('/')[1] ?? 'png';
            const archivo = new File([blob], `${name}.${extension}`, {
                type: blob.type,
            });

            setOriginal(archivo);
            setReeditando(true);
            setEnEdicion(archivo);
        } catch {
            setAviso('No se pudo cargar la imagen actual para editarla.');
        }
    }

    function confirmarEdicion(editado: File) {
        colocarArchivoEnCampo(editado);
        setVistaPrevia(URL.createObjectURL(editado));
        setQuitar(false);
        setEnEdicion(null);
    }

    function cancelarEdicion() {
        // Un archivo recién elegido y no confirmado se descarta por completo.
        if (!reeditando) {
            colocarArchivoEnCampo(null);
            setVistaPrevia(null);
            setOriginal(null);
        }

        setEnEdicion(null);
    }

    const imagen = quitar ? null : (vistaPrevia ?? urlActual);
    const sePuedeEditar = !quitar && (original !== null || urlActual !== null);

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{etiqueta}</Label>

            <div className="flex size-24 items-center justify-center overflow-hidden rounded-lg border bg-muted/30">
                {imagen ? (
                    <img
                        src={imagen}
                        alt={etiqueta}
                        className="size-full object-contain"
                    />
                ) : (
                    <span className="px-2 text-center text-xs text-muted-foreground">
                        Sin imagen
                    </span>
                )}
            </div>

            <Input
                ref={entradaRef}
                id={id}
                type="file"
                name={name}
                accept="image/*"
                onChange={alElegirArchivo}
            />
            <p className="text-sm text-muted-foreground">{ayuda}</p>

            {sePuedeEditar && imagen && (
                <div>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={editarImagenActual}
                    >
                        Recortar o girar
                    </Button>
                </div>
            )}
            {aviso && <p className="text-sm text-amber-600">{aviso}</p>}

            {urlActual && (
                <div className="flex items-center gap-2">
                    <Checkbox
                        id={`${id}-quitar`}
                        name={nombreQuitar}
                        checked={quitar}
                        onCheckedChange={(valor) => setQuitar(valor === true)}
                    />
                    <Label htmlFor={`${id}-quitar`}>Quitar imagen actual</Label>
                </div>
            )}
            <InputError message={error} />

            <ImageCropDialog
                archivo={enEdicion}
                titulo={`Editar ${etiqueta.toLowerCase()}`}
                aspectos={aspectos}
                maxLado={maxLado}
                onConfirmar={confirmarEdicion}
                onCancelar={cancelarEdicion}
            />
        </div>
    );
}
