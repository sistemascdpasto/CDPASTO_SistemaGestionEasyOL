import { ImgHTMLAttributes, useState } from 'react';

/**
 * Imagen que cae a un placeholder cuando el archivo no existe (p. ej. registros
 * antiguos cuyo archivo se perdió del disco), en vez de mostrar el ícono roto del navegador.
 */
export function SafeImage({ className, alt, ...props }: ImgHTMLAttributes<HTMLImageElement>) {
    const [failed, setFailed] = useState(false);

    if (failed) {
        return (
            <div className={`flex items-center justify-center bg-muted p-2 text-center text-xs text-muted-foreground ${className ?? ''}`}>
                Imagen no disponible
            </div>
        );
    }

    return <img alt={alt} className={className} onError={() => setFailed(true)} {...props} />;
}
