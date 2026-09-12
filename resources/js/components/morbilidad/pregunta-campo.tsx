import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { type PreguntaCatalogo, type RespuestaValor } from './types';

// ─── Helpers internos ─────────────────────────────────────────────────────────

/** Parsea el valor guardado como JSON-string a string[] o devuelve [] */
function parseChecks(valor: string | null): string[] {
    if (!valor) return [];
    try { return JSON.parse(valor) as string[]; } catch { return []; }
}

/** Serializa string[] a JSON-string para guardar en `valor` */
function encodeChecks(arr: string[]): string {
    return JSON.stringify(arr);
}

/**
 * Parsea el detalle de un segmento corporal.
 * Formato guardado: JSON con { frecuencia, severidad }
 */
interface SegmentoValor { frecuencia: string; severidad: string }
function parseSegmento(valor: string | null): SegmentoValor {
    if (!valor) return { frecuencia: '', severidad: '' };
    try { return JSON.parse(valor) as SegmentoValor; } catch { return { frecuencia: '', severidad: '' }; }
}
function encodeSegmento(sv: SegmentoValor): string { return JSON.stringify(sv); }

// ─── Componente principal ─────────────────────────────────────────────────────

/**
 * Campo editable de una pregunta del catálogo.
 * Soporta: si_no, si_no_detalle, aplica_detalle, texto_libre,
 *          numero, checkbox_multiple, actividades_salud,
 *          mano_dominante, segmento_corporal.
 */
export function PreguntaCampo({
    numero,
    pregunta,
    respuesta,
    onChange,
    errorValor,
    errorDetalle,
}: {
    numero: number;
    pregunta: PreguntaCatalogo;
    respuesta?: RespuestaValor;
    onChange: (numero: number, respuesta: RespuestaValor) => void;
    errorValor?: string;
    errorDetalle?: string;
}) {
    const valorPorDefecto = pregunta.tipo === 'aplica_detalle' ? 'No aplica' : null;
    const valor   = respuesta?.valor ?? valorPorDefecto;
    const detalle = respuesta?.detalle ?? '';

    // ── texto_libre ────────────────────────────────────────────────────────────
    if (pregunta.tipo === 'texto_libre') {
        return (
            <div className="grid gap-2 border-b border-border/60 pb-4 last:border-b-0 last:pb-0">
                <Label className="text-sm font-normal">{pregunta.texto}</Label>
                <textarea
                    className="min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    value={detalle}
                    onChange={e => onChange(numero, { valor: null, detalle: e.target.value })}
                />
            </div>
        );
    }

    // ── numero ─────────────────────────────────────────────────────────────────
    if (pregunta.tipo === 'numero') {
        return (
            <div className="grid gap-2 border-b border-border/60 pb-4 last:border-b-0 last:pb-0">
                <Label className="text-sm font-normal">
                    {pregunta.texto} <span className="text-red-500">*</span>
                </Label>
                <input
                    type="number"
                    min={0}
                    step="0.1"
                    value={valor ?? ''}
                    onChange={e => onChange(numero, { valor: e.target.value, detalle: null })}
                    className={`h-9 w-40 rounded-md border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-ring ${errorValor ? 'border-red-400' : 'border-input'}`}
                />
                <InputError message={errorValor} />
            </div>
        );
    }

    // ── mano_dominante ─────────────────────────────────────────────────────────
    if (pregunta.tipo === 'mano_dominante') {
        return (
            <div className="grid gap-2 border-b border-border/60 pb-4 last:border-b-0 last:pb-0">
                <Label className="text-sm font-normal">
                    {pregunta.texto} <span className="text-red-500">*</span>
                </Label>
                <div className="flex gap-3">
                    {['Derecha', 'Izquierda'].map(op => (
                        <button key={op} type="button"
                            data-selected={valor === op}
                            onClick={() => onChange(numero, { valor: op, detalle: null })}
                            className="rounded-md border px-4 py-1.5 text-xs font-medium transition-colors data-[selected=true]:border-primary data-[selected=true]:bg-primary data-[selected=true]:text-primary-foreground">
                            {op}
                        </button>
                    ))}
                </div>
                <InputError message={errorValor} />
            </div>
        );
    }

    // ── checkbox_multiple / actividades_salud ──────────────────────────────────
    if (pregunta.tipo === 'checkbox_multiple' || pregunta.tipo === 'actividades_salud') {
        const checks  = parseChecks(valor);
        const opciones = pregunta.opciones ?? [];
        const tieneOtro = pregunta.conOtro ?? false;

        const toggle = (op: string) => {
            const nuevo = checks.includes(op) ? checks.filter(x => x !== op) : [...checks, op];
            onChange(numero, { valor: encodeChecks(nuevo), detalle });
        };
        const setOtro = (txt: string) => {
            onChange(numero, { valor: encodeChecks(checks), detalle: txt });
        };

        return (
            <div className="grid gap-2 border-b border-border/60 pb-4 last:border-b-0 last:pb-0">
                <Label className="text-sm font-normal">
                    {pregunta.texto} <span className="text-red-500">*</span>
                </Label>
                <div className="grid gap-1.5 sm:grid-cols-2">
                    {opciones.map(op => (
                        <label key={op} className="flex cursor-pointer items-center gap-2 rounded-md border border-border px-3 py-1.5 text-xs transition-colors hover:bg-muted/40 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                            <input
                                type="checkbox"
                                checked={checks.includes(op)}
                                onChange={() => toggle(op)}
                                className="accent-primary h-3.5 w-3.5 shrink-0"
                            />
                            {op}
                        </label>
                    ))}
                </div>
                {tieneOtro && (
                    <div className="flex items-center gap-2 mt-1">
                        <label className="flex cursor-pointer items-center gap-2 text-xs">
                            <input
                                type="checkbox"
                                checked={detalle !== ''}
                                onChange={e => { if (!e.target.checked) setOtro(''); }}
                                className="accent-primary h-3.5 w-3.5"
                            />
                            <span className="font-medium">Otro:</span>
                        </label>
                        <input
                            type="text"
                            value={detalle}
                            onChange={e => setOtro(e.target.value)}
                            placeholder="Especifique..."
                            className="h-7 flex-1 rounded-md border border-input bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                        />
                    </div>
                )}
                <InputError message={errorValor} />
            </div>
        );
    }

    // ── segmento_corporal ──────────────────────────────────────────────────────
    // Valor guardado: JSON { frecuencia, severidad }
    // detalle no se usa en este tipo.
    if (pregunta.tipo === 'segmento_corporal') {
        const sv = parseSegmento(valor);
        const FRECUENCIAS = ['NUNCA', 'RARA VEZ', 'FRECUENTE', 'CONTINUO'];
        const SEVERIDADES = ['LEVE', 'MODERADO', 'SEVERO'];
        const requiereSeveridad = sv.frecuencia !== '' && sv.frecuencia !== 'NUNCA';

        const setFrecuencia = (f: string) => {
            const nuevo: SegmentoValor = {
                frecuencia: f,
                severidad: f === 'NUNCA' ? '' : sv.severidad,
            };
            onChange(numero, { valor: encodeSegmento(nuevo), detalle: null });
        };
        const setSeveridad = (s: string) => {
            onChange(numero, { valor: encodeSegmento({ ...sv, severidad: s }), detalle: null });
        };

        return (
            <div className="grid gap-2 border-b border-border/60 pb-4 last:border-b-0 last:pb-0">
                <div className="flex items-center justify-between gap-2 flex-wrap">
                    <Label className="text-sm font-medium">
                        {pregunta.segmento ?? pregunta.texto}
                        <span className="text-red-500 ml-0.5">*</span>
                    </Label>
                </div>

                {/* Frecuencia */}
                <div className="flex flex-wrap items-center gap-1.5">
                    <span className="text-[11px] font-semibold text-muted-foreground w-20 shrink-0">Frecuencia:</span>
                    {FRECUENCIAS.map(f => (
                        <button key={f} type="button"
                            data-selected={sv.frecuencia === f}
                            onClick={() => setFrecuencia(f)}
                            className="rounded-md border px-2.5 py-1 text-[11px] font-medium transition-colors data-[selected=true]:border-primary data-[selected=true]:bg-primary data-[selected=true]:text-primary-foreground">
                            {f}
                        </button>
                    ))}
                </div>

                {/* Severidad — solo si la frecuencia no es NUNCA */}
                {requiereSeveridad && (
                    <div className="flex flex-wrap items-center gap-1.5">
                        <span className="text-[11px] font-semibold text-muted-foreground w-20 shrink-0">Severidad:</span>
                        {SEVERIDADES.map(s => (
                            <button key={s} type="button"
                                data-selected={sv.severidad === s}
                                onClick={() => setSeveridad(s)}
                                className="rounded-md border px-2.5 py-1 text-[11px] font-medium transition-colors data-[selected=true]:border-amber-500 data-[selected=true]:bg-amber-500 data-[selected=true]:text-white">
                                {s}
                            </button>
                        ))}
                    </div>
                )}

                <InputError message={errorValor} />
            </div>
        );
    }

    // ── si_no / si_no_detalle / aplica_detalle ────────────────────────────────
    const opciones = pregunta.tipo === 'aplica_detalle' ? ['No aplica', 'Aplica'] : ['Si', 'No'];
    const mostrarDetalle =
        (pregunta.tipo === 'si_no_detalle' && valor === 'Si') ||
        (pregunta.tipo === 'aplica_detalle' && valor === 'Aplica');

    const setValor = (nuevoValor: string) =>
        onChange(numero, {
            valor: nuevoValor,
            detalle: nuevoValor === 'Si' || nuevoValor === 'Aplica' ? detalle : '',
        });

    return (
        <div className="grid gap-2 border-b border-border/60 pb-4 last:border-b-0 last:pb-0">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <Label className="flex-1 text-sm font-normal">{pregunta.texto}</Label>
                <div className="flex shrink-0 gap-1.5">
                    {opciones.map(opcion => (
                        <button key={opcion} type="button"
                            data-selected={valor === opcion}
                            onClick={() => setValor(opcion)}
                            className="rounded-md border px-3 py-1.5 text-xs font-medium transition-colors data-[selected=true]:border-primary data-[selected=true]:bg-primary data-[selected=true]:text-primary-foreground">
                            {opcion}
                        </button>
                    ))}
                </div>
            </div>
            <InputError message={errorValor} />
            {mostrarDetalle && (
                <div className="grid gap-1">
                    <textarea
                        placeholder="Especifique..."
                        className="min-h-16 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        value={detalle}
                        onChange={e => onChange(numero, { valor, detalle: e.target.value })}
                    />
                    <InputError message={errorDetalle} />
                </div>
            )}
        </div>
    );
}
