const MAYUSCULAS = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
const MINUSCULAS = 'abcdefghijkmnopqrstuvwxyz';
const NUMEROS = '23456789';
const SIMBOLOS = '!@#$%*-_+?';

/**
 * Entero aleatorio en [0, max) sin sesgo de módulo, con el generador seguro
 * del navegador.
 */
function enteroAleatorio(max: number): number {
    const limite = Math.floor(0x100000000 / max) * max;
    const buffer = new Uint32Array(1);

    do {
        crypto.getRandomValues(buffer);
    } while (buffer[0] >= limite);

    return buffer[0] % max;
}

function caracterDe(conjunto: string): string {
    return conjunto[enteroAleatorio(conjunto.length)];
}

/**
 * Genera una contraseña aleatoria con al menos una mayúscula, una minúscula,
 * un número y un símbolo. Excluye caracteres ambiguos (0/O, 1/l/I) para que
 * sea fácil de dictar o copiar a mano.
 */
export function generatePassword(length = 16): string {
    const grupos = [MAYUSCULAS, MINUSCULAS, NUMEROS, SIMBOLOS];
    const todos = grupos.join('');

    const caracteres = [
        ...grupos.map(caracterDe),
        ...Array.from({ length: Math.max(length - grupos.length, 0) }, () =>
            caracterDe(todos),
        ),
    ];

    for (let i = caracteres.length - 1; i > 0; i--) {
        const j = enteroAleatorio(i + 1);
        [caracteres[i], caracteres[j]] = [caracteres[j], caracteres[i]];
    }

    return caracteres.join('');
}
