import { type ReactNode } from 'react';

/**
 * Renderizador de markdown minimalista para las burbujas del chatbot, sin
 * dependencias externas (el widget se monta en todas las páginas, no está
 * code-split como welcome.tsx, así que conviene mantenerlo liviano).
 *
 * Cubre lo que efectivamente produce el modelo: negrillas, cursivas, código
 * en línea, enlaces, encabezados, listas (con y sin numerar), tablas GFM y
 * líneas horizontales. No interpreta HTML crudo en ningún momento — todo se
 * arma como elementos de React a partir de texto, así que es seguro aunque
 * el modelo devuelva algo como "<script>" (se muestra como texto plano).
 */

type Bloque =
    | { tipo: 'parrafo'; texto: string }
    | { tipo: 'encabezado'; nivel: number; texto: string }
    | { tipo: 'regla' }
    | { tipo: 'lista'; ordenada: boolean; items: string[] }
    | { tipo: 'tabla'; encabezados: string[]; filas: string[][] };

function dividirFilaTabla(linea: string): string[] {
    const sinBordes = linea.trim().replace(/^\|/, '').replace(/\|$/, '');

    return sinBordes.split('|').map((celda) => celda.trim());
}

function esLineaSeparadorTabla(linea: string): boolean {
    return /^\|?\s*:?-{2,}:?\s*(\|\s*:?-{2,}:?\s*)+\|?$/.test(linea.trim());
}

function parsearBloques(texto: string): Bloque[] {
    const lineas = texto.replace(/\r\n/g, '\n').split('\n');
    const bloques: Bloque[] = [];
    let indice = 0;

    while (indice < lineas.length) {
        const linea = lineas[indice];

        if (linea.trim() === '') {
            indice++;
            continue;
        }

        // Regla horizontal: ---, ***, ___ (línea completa)
        if (/^(-{3,}|\*{3,}|_{3,})$/.test(linea.trim())) {
            bloques.push({ tipo: 'regla' });
            indice++;
            continue;
        }

        // Encabezado: #, ##, ### ...
        const encabezadoMatch = /^(#{1,6})\s+(.*)$/.exec(linea);
        if (encabezadoMatch) {
            bloques.push({ tipo: 'encabezado', nivel: encabezadoMatch[1].length, texto: encabezadoMatch[2].trim() });
            indice++;
            continue;
        }

        // Tabla GFM: línea con | seguida de una línea separadora ---|---
        if (linea.includes('|') && lineas[indice + 1] && esLineaSeparadorTabla(lineas[indice + 1])) {
            const encabezados = dividirFilaTabla(linea);
            const filas: string[][] = [];
            let cursor = indice + 2;
            while (cursor < lineas.length && lineas[cursor].trim() !== '' && lineas[cursor].includes('|')) {
                filas.push(dividirFilaTabla(lineas[cursor]));
                cursor++;
            }
            bloques.push({ tipo: 'tabla', encabezados, filas });
            indice = cursor;
            continue;
        }

        // Lista (con o sin numerar)
        const itemNoOrdenado = /^\s*[-*+]\s+(.*)$/;
        const itemOrdenado = /^\s*\d+[.)]\s+(.*)$/;
        if (itemNoOrdenado.test(linea) || itemOrdenado.test(linea)) {
            const ordenada = itemOrdenado.test(linea);
            const patron = ordenada ? itemOrdenado : itemNoOrdenado;
            const items: string[] = [];
            let cursor = indice;
            while (cursor < lineas.length && patron.test(lineas[cursor])) {
                items.push(patron.exec(lineas[cursor])![1].trim());
                cursor++;
            }
            bloques.push({ tipo: 'lista', ordenada, items });
            indice = cursor;
            continue;
        }

        // Párrafo: junta líneas consecutivas hasta encontrar una línea vacía
        // u otro tipo de bloque.
        const parrafoLineas: string[] = [];
        let cursor = indice;
        while (
            cursor < lineas.length &&
            lineas[cursor].trim() !== '' &&
            !/^(#{1,6})\s+/.test(lineas[cursor]) &&
            !/^(-{3,}|\*{3,}|_{3,})$/.test(lineas[cursor].trim()) &&
            !itemNoOrdenado.test(lineas[cursor]) &&
            !itemOrdenado.test(lineas[cursor])
        ) {
            parrafoLineas.push(lineas[cursor]);
            cursor++;
        }
        bloques.push({ tipo: 'parrafo', texto: parrafoLineas.join('\n') });
        indice = cursor;
    }

    return bloques;
}

/**
 * Aplica formato en línea (negrilla, cursiva, código, enlaces) y respeta
 * saltos de línea simples dentro de un bloque.
 */
function renderizarEnLinea(texto: string, keyBase: string): ReactNode[] {
    // El modelo a veces usa <br> (HTML) en vez de saltos de línea reales,
    // sobre todo dentro de celdas de tabla (donde una celda GFM no puede
    // contener un "\n" literal). Se normaliza a "\n" antes de tokenizar, así
    // que el resultado es el mismo <br /> de React que ya generan los saltos
    // de línea de verdad — nunca se interpreta como HTML crudo.
    const lineas = texto.replace(/<br\s*\/?>/gi, '\n').split('\n');

    return lineas.flatMap((linea, indiceLinea) => {
        const tokens: ReactNode[] = [];
        const patron = /(\*\*[^*]+\*\*|__[^_]+__|`[^`]+`|\[[^[\]]+\]\([^()\s]+\)|\*[^*\n]+\*|_[^_\n]+_)/g;
        let ultimoIndice = 0;
        let coincidencia: RegExpExecArray | null;
        let contador = 0;

        while ((coincidencia = patron.exec(linea)) !== null) {
            if (coincidencia.index > ultimoIndice) {
                tokens.push(linea.slice(ultimoIndice, coincidencia.index));
            }

            const fragmento = coincidencia[0];
            const key = `${keyBase}-${indiceLinea}-${contador++}`;

            if (fragmento.startsWith('**') || fragmento.startsWith('__')) {
                tokens.push(
                    <strong key={key} className="font-semibold">
                        {fragmento.slice(2, -2)}
                    </strong>,
                );
            } else if (fragmento.startsWith('`')) {
                tokens.push(
                    <code key={key} className="rounded bg-black/10 px-1 py-0.5 font-mono text-[0.85em] dark:bg-white/15">
                        {fragmento.slice(1, -1)}
                    </code>,
                );
            } else if (fragmento.startsWith('[')) {
                const enlaceMatch = /^\[([^[\]]+)\]\(([^()\s]+)\)$/.exec(fragmento);
                if (enlaceMatch) {
                    tokens.push(
                        <a
                            key={key}
                            href={enlaceMatch[2]}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="underline underline-offset-2 hover:opacity-80"
                        >
                            {enlaceMatch[1]}
                        </a>,
                    );
                } else {
                    tokens.push(fragmento);
                }
            } else {
                // *cursiva* o _cursiva_
                tokens.push(<em key={key}>{fragmento.slice(1, -1)}</em>);
            }

            ultimoIndice = coincidencia.index + fragmento.length;
        }

        if (ultimoIndice < linea.length) {
            tokens.push(linea.slice(ultimoIndice));
        }

        return indiceLinea < lineas.length - 1 ? [...tokens, <br key={`${keyBase}-br-${indiceLinea}`} />] : tokens;
    });
}

const TAMANOS_ENCABEZADO: Record<number, string> = {
    1: 'text-base font-bold',
    2: 'text-[0.95rem] font-bold',
    3: 'text-sm font-semibold',
    4: 'text-sm font-semibold',
    5: 'text-sm font-semibold',
    6: 'text-sm font-semibold',
};

export function MarkdownLite({ texto }: { texto: string }) {
    const bloques = parsearBloques(texto);

    return (
        <div className="flex flex-col gap-2 [overflow-wrap:anywhere]">
            {bloques.map((bloque, indice) => {
                const key = `bloque-${indice}`;

                if (bloque.tipo === 'regla') {
                    return <hr key={key} className="border-border" />;
                }

                if (bloque.tipo === 'encabezado') {
                    return (
                        <p key={key} className={TAMANOS_ENCABEZADO[bloque.nivel] ?? TAMANOS_ENCABEZADO[6]}>
                            {renderizarEnLinea(bloque.texto, key)}
                        </p>
                    );
                }

                if (bloque.tipo === 'lista') {
                    const ListTag = bloque.ordenada ? 'ol' : 'ul';
                    return (
                        <ListTag key={key} className={bloque.ordenada ? 'list-decimal space-y-0.5 pl-5' : 'list-disc space-y-0.5 pl-5'}>
                            {bloque.items.map((item, indiceItem) => (
                                <li key={`${key}-${indiceItem}`}>{renderizarEnLinea(item, `${key}-${indiceItem}`)}</li>
                            ))}
                        </ListTag>
                    );
                }

                if (bloque.tipo === 'tabla') {
                    return (
                        <div key={key} className="-mx-1 overflow-x-auto">
                            <table className="w-full min-w-max border-collapse text-xs">
                                <thead>
                                    <tr className="border-b border-border">
                                        {bloque.encabezados.map((celda, indiceCelda) => (
                                            <th key={indiceCelda} className="px-2 py-1 text-left font-semibold whitespace-nowrap">
                                                {renderizarEnLinea(celda, `${key}-th-${indiceCelda}`)}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {bloque.filas.map((fila, indiceFila) => (
                                        <tr key={indiceFila} className="border-b border-border/60 last:border-0">
                                            {fila.map((celda, indiceCelda) => (
                                                <td key={indiceCelda} className="px-2 py-1 align-top">
                                                    {renderizarEnLinea(celda, `${key}-td-${indiceFila}-${indiceCelda}`)}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    );
                }

                return <p key={key}>{renderizarEnLinea(bloque.texto, key)}</p>;
            })}
        </div>
    );
}
