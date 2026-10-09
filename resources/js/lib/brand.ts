/**
 * Nombre de marca que se muestra en el título de la pestaña. Lo fija el
 * diseño principal con el nombre de la empresa del usuario; mientras no haya
 * empresa se usa el de la aplicación.
 */
const nombreAplicacion = import.meta.env.VITE_APP_NAME || 'Laravel';

let nombreMarca = nombreAplicacion;

export function getNombreMarca(): string {
    return nombreMarca;
}

export function setNombreMarca(nombre: string | null | undefined): void {
    nombreMarca = nombre || nombreAplicacion;
}
