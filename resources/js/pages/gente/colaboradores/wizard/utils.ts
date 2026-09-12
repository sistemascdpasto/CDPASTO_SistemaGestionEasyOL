/**
 * Un campo de archivos múltiples (`SimpleFileField`) valida tanto el array
 * completo (`campo`) como cada archivo individual (`campo.0`, `campo.1`...).
 * Laravel devuelve los errores con esas claves de punto, así que un simple
 * `errors[campo]` se queda callado cuando el rechazo fue de un archivo
 * puntual (ej. "no es un pdf/imagen válido" o "pesa más de 5MB") — el
 * usuario nunca se entera por qué el paso no avanzó.
 */
export function errorDeArchivo(errors: Record<string, string | undefined>, campo: string): string | undefined {
    if (errors[campo]) return errors[campo];

    const clave = Object.keys(errors).find((key) => key.startsWith(`${campo}.`));

    return clave ? errors[clave] : undefined;
}
