import type { PixelCrop } from 'react-image-crop';

/** Tipos que se pueden recortar en un canvas; el resto (SVG, ICO, GIF) se sube tal cual. */
const TIPOS_EDITABLES = ['image/png', 'image/jpeg', 'image/webp'];

export function esImagenEditable(tipo: string): boolean {
    return TIPOS_EDITABLES.includes(tipo);
}

function cargarImagen(src: string): Promise<HTMLImageElement> {
    return new Promise((resolver, rechazar) => {
        const imagen = new Image();

        imagen.onload = () => resolver(imagen);
        imagen.onerror = () =>
            rechazar(new Error('No se pudo cargar la imagen.'));
        imagen.src = src;
    });
}

function canvasABlob(
    canvas: HTMLCanvasElement,
    tipo: string,
    calidad?: number,
): Promise<Blob> {
    return new Promise((resolver, rechazar) => {
        canvas.toBlob(
            (blob) =>
                blob
                    ? resolver(blob)
                    : rechazar(new Error('No se pudo generar la imagen.')),
            tipo,
            calidad,
        );
    });
}

/**
 * Devuelve la URL de una copia de la imagen girada `grados` (múltiplo de 90).
 * Quien la llama debe liberar la URL con `URL.revokeObjectURL`.
 */
export async function rotarImagen(
    srcOriginal: string,
    grados: number,
    tipo: string,
): Promise<string> {
    const normalizados = ((grados % 360) + 360) % 360;
    const imagen = await cargarImagen(srcOriginal);

    const intercambia = normalizados === 90 || normalizados === 270;
    const canvas = document.createElement('canvas');

    canvas.width = intercambia ? imagen.naturalHeight : imagen.naturalWidth;
    canvas.height = intercambia ? imagen.naturalWidth : imagen.naturalHeight;

    const contexto = canvas.getContext('2d');

    if (!contexto) {
        throw new Error('El navegador no permite editar imágenes.');
    }

    contexto.translate(canvas.width / 2, canvas.height / 2);
    contexto.rotate((normalizados * Math.PI) / 180);
    contexto.drawImage(
        imagen,
        -imagen.naturalWidth / 2,
        -imagen.naturalHeight / 2,
    );

    return URL.createObjectURL(await canvasABlob(canvas, tipo));
}

/**
 * Recorta la zona elegida de la imagen mostrada y la devuelve como archivo.
 *
 * `zona` viene en píxeles de pantalla; se escala al tamaño real de la imagen.
 * Si el resultado supera `maxLado` en alguno de sus lados se reduce
 * proporcionalmente, para que el archivo pese poco.
 */
export async function recortarImagen(
    imagen: HTMLImageElement,
    zona: PixelCrop,
    tipo: string,
    nombre: string,
    maxLado: number,
): Promise<File> {
    const escalaX = imagen.naturalWidth / imagen.width;
    const escalaY = imagen.naturalHeight / imagen.height;

    const origenAncho = zona.width * escalaX;
    const origenAlto = zona.height * escalaY;
    const reduccion = Math.min(1, maxLado / Math.max(origenAncho, origenAlto));

    const canvas = document.createElement('canvas');

    canvas.width = Math.max(1, Math.round(origenAncho * reduccion));
    canvas.height = Math.max(1, Math.round(origenAlto * reduccion));

    const contexto = canvas.getContext('2d');

    if (!contexto) {
        throw new Error('El navegador no permite editar imágenes.');
    }

    contexto.imageSmoothingQuality = 'high';
    contexto.drawImage(
        imagen,
        zona.x * escalaX,
        zona.y * escalaY,
        origenAncho,
        origenAlto,
        0,
        0,
        canvas.width,
        canvas.height,
    );

    const tipoSalida = tipo === 'image/jpeg' ? 'image/jpeg' : 'image/png';
    const blob = await canvasABlob(canvas, tipoSalida, 0.92);
    const extension = tipoSalida === 'image/jpeg' ? 'jpg' : 'png';
    const base = nombre.replace(/\.[^.]+$/, '') || 'imagen';

    return new File([blob], `${base}.${extension}`, { type: tipoSalida });
}
