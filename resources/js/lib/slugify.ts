/**
 * Convierte un texto en slug: minúsculas, sin tildes y con guiones. Sigue la
 * misma regla que `Str::slug` del servidor, que valida el resultado final.
 */
export function slugify(texto: string): string {
    return texto
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}
