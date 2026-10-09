/**
 * Minúsculas y sin tildes, para comparar textos al buscar: así "camion"
 * encuentra "Camión" y "ORDEN" encuentra "orden".
 */
export function normalizarTexto(texto: string): string {
    return texto
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase();
}
