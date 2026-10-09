/**
 * Clases que hacen que un campo (input, select o selector con búsqueda) se vea
 * como la celda de una hoja de cálculo: sin borde ni fondo, con un resaltado
 * suave al pasar el ratón y un contorno solo cuando tiene el foco.
 *
 * Se aplican con `cn()`, que resuelve los conflictos con las clases del campo
 * normal (el borde, el redondeo y la sombra).
 */
export const ESTILO_CELDA =
    'rounded-none border-transparent bg-transparent shadow-none hover:bg-muted/40 focus-visible:border-ring focus-visible:ring-1 focus-visible:ring-ring dark:bg-transparent';
