import { Badge } from '@/components/ui/badge';
import { type RespuestasState, type SeccionCatalogo } from './types';

/** Vista de solo lectura de una sección completa, usada tanto en el detalle del colaborador como en el visor de SST. */
export function SeccionLectura({ seccion, respuestas }: { seccion: SeccionCatalogo; respuestas: RespuestasState }) {
    return (
        <div className="space-y-3">
            {Object.entries(seccion.preguntas).map(([numeroStr, pregunta]) => {
                const numero = Number(numeroStr);
                const respuesta = respuestas[numero];

                return (
                    <div key={numero} className="border-b border-border/60 pb-3 last:border-b-0 last:pb-0">
                        <p className="text-sm text-foreground">{pregunta.texto}</p>
                        {pregunta.tipo === 'texto_libre' ? (
                            <p className="mt-1 text-sm text-muted-foreground">{respuesta?.detalle || '—'}</p>
                        ) : (
                            <div className="mt-1 flex items-start gap-2">
                                <Badge variant={respuesta?.valor === 'Si' || respuesta?.valor === 'Aplica' ? 'destructive' : 'secondary'}>
                                    {respuesta?.valor ?? 'Sin responder'}
                                </Badge>
                                {respuesta?.detalle && <p className="text-xs text-muted-foreground">{respuesta.detalle}</p>}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
