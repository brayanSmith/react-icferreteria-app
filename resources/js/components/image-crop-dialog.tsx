import { RotateCcw, RotateCw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { SyntheticEvent } from 'react';
import ReactCrop, {
    centerCrop,
    convertToPixelCrop,
    makeAspectCrop,
} from 'react-image-crop';
import type { Crop, PixelCrop } from 'react-image-crop';
import 'react-image-crop/dist/ReactCrop.css';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { recortarImagen, rotarImagen } from '@/lib/image-editing';

export type AspectoRecorte = {
    etiqueta: string;
    /** Proporción ancho/alto; sin valor, el recorte es libre. */
    valor?: number;
};

type ImageCropDialogProps = {
    /** Imagen a editar; el modal está abierto mientras haya un archivo. */
    archivo: File | null;
    titulo: string;
    /** Proporciones disponibles. Con una sola, queda fija. */
    aspectos: AspectoRecorte[];
    /** Lado máximo, en píxeles, de la imagen resultante. */
    maxLado: number;
    onConfirmar: (archivo: File) => void;
    onCancelar: () => void;
};

function recorteInicial(
    aspecto: number | undefined,
    ancho: number,
    alto: number,
): Crop {
    if (aspecto) {
        return centerCrop(
            makeAspectCrop({ unit: '%', width: 90 }, aspecto, ancho, alto),
            ancho,
            alto,
        );
    }

    return { unit: '%', x: 5, y: 5, width: 90, height: 90 };
}

/**
 * Modal para recortar y girar una imagen antes de subirla.
 */
export default function ImageCropDialog({
    archivo,
    titulo,
    aspectos,
    maxLado,
    onConfirmar,
    onCancelar,
}: ImageCropDialogProps) {
    const imagenRef = useRef<HTMLImageElement>(null);
    const [urlOriginal, setUrlOriginal] = useState<string | null>(null);
    const [src, setSrc] = useState<string | null>(null);
    const [grados, setGrados] = useState(0);
    const [aspecto, setAspecto] = useState<number | undefined>(
        aspectos[0]?.valor,
    );
    const [crop, setCrop] = useState<Crop>();
    const [zona, setZona] = useState<PixelCrop>();
    const [procesando, setProcesando] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Prepara el archivo cada vez que se abre el modal con uno nuevo.
    useEffect(() => {
        if (!archivo) {
            return;
        }

        const url = URL.createObjectURL(archivo);

        setUrlOriginal(url);
        setGrados(0);
        setAspecto(aspectos[0]?.valor);
        setCrop(undefined);
        setZona(undefined);
        setError(null);

        return () => URL.revokeObjectURL(url);
        // `aspectos` es estable por pantalla; solo importa cuándo cambia el archivo.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [archivo]);

    // Genera la imagen girada que se muestra y se recorta.
    useEffect(() => {
        if (!urlOriginal || !archivo) {
            return;
        }

        if (grados === 0) {
            setSrc(urlOriginal);

            return;
        }

        let vigente = true;
        let urlGirada: string | null = null;

        rotarImagen(urlOriginal, grados, archivo.type)
            .then((url) => {
                urlGirada = url;

                if (vigente) {
                    setSrc(url);
                } else {
                    URL.revokeObjectURL(url);
                }
            })
            .catch(() => setError('No se pudo girar la imagen.'));

        return () => {
            vigente = false;

            if (urlGirada) {
                URL.revokeObjectURL(urlGirada);
            }
        };
    }, [urlOriginal, grados, archivo]);

    function aplicarRecorteInicial(proporcion: number | undefined) {
        const imagen = imagenRef.current;

        if (!imagen) {
            return;
        }

        const nuevo = recorteInicial(proporcion, imagen.width, imagen.height);

        setCrop(nuevo);
        setZona(convertToPixelCrop(nuevo, imagen.width, imagen.height));
    }

    function alCargarImagen(_evento: SyntheticEvent<HTMLImageElement>) {
        aplicarRecorteInicial(aspecto);
    }

    function cambiarAspecto(proporcion: number | undefined) {
        setAspecto(proporcion);
        aplicarRecorteInicial(proporcion);
    }

    async function confirmar() {
        const imagen = imagenRef.current;

        if (!imagen || !archivo) {
            return;
        }

        setProcesando(true);
        setError(null);

        try {
            const area: PixelCrop =
                zona && zona.width > 0 && zona.height > 0
                    ? zona
                    : {
                          unit: 'px',
                          x: 0,
                          y: 0,
                          width: imagen.width,
                          height: imagen.height,
                      };

            onConfirmar(
                await recortarImagen(
                    imagen,
                    area,
                    archivo.type,
                    archivo.name,
                    maxLado,
                ),
            );
        } catch {
            setError('No se pudo procesar la imagen. Prueba con otra.');
        } finally {
            setProcesando(false);
        }
    }

    return (
        <Dialog
            open={archivo !== null}
            onOpenChange={(abierto) => {
                if (!abierto) {
                    onCancelar();
                }
            }}
        >
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{titulo}</DialogTitle>
                    <DialogDescription>
                        Arrastra los bordes para elegir la zona que quieres
                        conservar. También puedes girar la imagen.
                    </DialogDescription>
                </DialogHeader>

                <div className="flex flex-wrap items-center gap-2">
                    {aspectos.length > 1 &&
                        aspectos.map((opcion) => (
                            <Button
                                key={opcion.etiqueta}
                                type="button"
                                size="sm"
                                variant={
                                    aspecto === opcion.valor
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() => cambiarAspecto(opcion.valor)}
                            >
                                {opcion.etiqueta}
                            </Button>
                        ))}
                    <div className="ml-auto flex gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            aria-label="Girar a la izquierda"
                            onClick={() => setGrados((actual) => actual - 90)}
                        >
                            <RotateCcw />
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            aria-label="Girar a la derecha"
                            onClick={() => setGrados((actual) => actual + 90)}
                        >
                            <RotateCw />
                        </Button>
                    </div>
                </div>

                <div className="flex justify-center rounded-lg bg-muted/40 p-2">
                    {src && (
                        <ReactCrop
                            crop={crop}
                            aspect={aspecto}
                            onChange={(_pixeles, porcentaje) =>
                                setCrop(porcentaje)
                            }
                            onComplete={(pixeles) => setZona(pixeles)}
                        >
                            <img
                                ref={imagenRef}
                                src={src}
                                alt="Imagen a recortar"
                                onLoad={alCargarImagen}
                                className="max-h-[55vh] max-w-full"
                            />
                        </ReactCrop>
                    )}
                </div>

                {error && <p className="text-sm text-red-600">{error}</p>}

                <DialogFooter className="gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={onCancelar}
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        disabled={procesando || !src}
                        onClick={confirmar}
                    >
                        {procesando ? 'Procesando…' : 'Aplicar'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
