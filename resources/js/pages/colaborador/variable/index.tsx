import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { DollarSign, Star, TrendingUp, User, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mi Variable', href: '/portal/mi-variable' },
];

interface ColaboradorInfo {
    id: number;
    nombre_completo: string;
    cedula: string;
    cargo: string;
    area: string;
    imagen: string | null;
}

interface IncentivoRow {
    id: number;
    mes: string | null;
    indicador_1: string | null; pilar_1: string | null;
    indicador_2: string | null; pilar_2: string | null;
    indicador_3: string | null; pilar_3: string | null;
    valor_indicador_1: string | null;
    valor_indicador_2: string | null;
    valor_indicador_3: string | null;
    total_4: string | null;
    meta_4: string | null;
    created_at: string;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function fmtMoney(v: string | null | undefined): string {
    if (!v) return '—';
    const n = parseFloat(v);
    if (isNaN(n)) return v;
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(n);
}

function cumple(v: string | null, m: string | null): boolean | null {
    const a = parseFloat(v ?? ''), b = parseFloat(m ?? '');
    if (isNaN(a) || isNaN(b)) return null;
    return a >= b;
}

function pct(v: string | null, m: string | null): number | null {
    const a = parseFloat(v ?? ''), b = parseFloat(m ?? '');
    if (isNaN(a) || isNaN(b) || b === 0) return null;
    return Math.round((a / b) * 100);
}

// ── Barra animada ─────────────────────────────────────────────────────────────

function Barra({ p, ok }: { p: number; ok: boolean | null }) {
    const [w, setW] = useState(0);
    useEffect(() => { const t = setTimeout(() => setW(Math.min(p, 100)), 200); return () => clearTimeout(t); }, [p]);
    const color = ok === true ? 'bg-blue-500' : ok === false ? 'bg-rose-400' : 'bg-slate-300';
    return (
        <div className="h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
            <div className={`h-full rounded-full transition-all duration-700 ${color}`} style={{ width: `${w}%` }} />
        </div>
    );
}

// ── Tarjeta de indicador variable ─────────────────────────────────────────────

function TarjetaVariable({ nombre, pilar, valor, num }: {
    nombre: string | null; pilar: string | null; valor: string | null; num: number;
}) {
    if (!valor) return null;
    const v = parseFloat(valor);
    const tieneValor = !isNaN(v) && v > 0;
    return (
        <div className="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800/50 dark:bg-blue-900/20">
            {pilar && <span className="mb-1 inline-block rounded-full bg-white/60 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-400">{pilar}</span>}
            <p className="text-xs font-bold text-slate-800 dark:text-slate-100 mb-2">{nombre ?? `Indicador ${num}`}</p>
            <div className="flex items-center gap-1">
                <DollarSign className="size-4 text-blue-600 dark:text-blue-400" />
                <span className={`text-xl font-black tabular-nums ${tieneValor ? 'text-blue-700 dark:text-blue-400' : 'text-slate-400'}`}>
                    {fmtMoney(valor)}
                </span>
            </div>
        </div>
    );
}

// ── Card de un registro ───────────────────────────────────────────────────────

function RegistroCard({ item }: { item: IncentivoRow }) {
    const okTotal  = cumple(item.total_4, item.meta_4);
    const pctTotal = pct(item.total_4, item.meta_4) ?? 0;

    const indicadores = [
        { nombre: item.indicador_1, pilar: item.pilar_1, valor: item.valor_indicador_1 },
        { nombre: item.indicador_2, pilar: item.pilar_2, valor: item.valor_indicador_2 },
        { nombre: item.indicador_3, pilar: item.pilar_3, valor: item.valor_indicador_3 },
    ].filter(i => i.valor !== null && parseFloat(i.valor ?? '') > 0);

    return (
        <div className="rounded-2xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border overflow-hidden">
            {/* Encabezado */}
            <div className="flex items-center justify-between gap-3 border-b border-sidebar-border/50 bg-muted/30 px-4 py-3">
                <span className="text-sm font-bold text-foreground">{item.mes ?? '—'}</span>
                {(item.total_4 || item.meta_4) && (
                    <div className="flex items-center gap-2">
                        <span className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${
                            okTotal === true ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                            : okTotal === false ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
                            : 'bg-slate-100 text-slate-500'
                        }`}>
                            {fmtMoney(item.total_4)}
                            {item.meta_4 && <span className="ml-1 opacity-60">/ {fmtMoney(item.meta_4)}</span>}
                        </span>
                        {okTotal === true && <Star className="size-4 fill-amber-400 text-amber-400" />}
                    </div>
                )}
            </div>

            {/* Barra total */}
            {item.total_4 && item.meta_4 && (
                <div className="px-4 pt-3 pb-1">
                    <Barra p={pctTotal} ok={okTotal} />
                    <div className="mt-1 flex justify-between text-[10px] text-muted-foreground">
                        <span>{pctTotal}% de la meta</span>
                        {okTotal === false && item.total_4 && item.meta_4 && (
                            <span className="text-rose-500">
                                Faltan {fmtMoney(String(Math.max(0, parseFloat(item.meta_4) - parseFloat(item.total_4))))}
                            </span>
                        )}
                    </div>
                </div>
            )}

            {/* Indicadores de valor */}
            {indicadores.length > 0 && (
                <div className="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                    {indicadores.map((ind, i) => (
                        <TarjetaVariable key={i} num={i + 1} nombre={ind.nombre} pilar={ind.pilar} valor={ind.valor} />
                    ))}
                </div>
            )}
        </div>
    );
}

// ── Página ────────────────────────────────────────────────────────────────────

export default function MiVariable({
    colaborador, incentivos, meses, mes,
}: {
    colaborador: ColaboradorInfo;
    incentivos: IncentivoRow[];
    meses: string[];
    mes: string;
}) {
    const [mesActual, setMes] = useState(mes);
    const isFirst = useRef(true);

    useEffect(() => {
        if (isFirst.current) { isFirst.current = false; return; }
        router.get(route('portal.mi-variable'), { mes: mesActual }, { preserveState: true, replace: true });
    }, [mesActual]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mi Variable" />
            <div className="flex flex-col gap-5 px-4 pb-10 pt-4 sm:px-6">

                {/* Título */}
                <div>
                    <h1 className="text-xl font-bold text-foreground sm:text-3xl">Mi Variable</h1>
                    <p className="mt-0.5 text-sm text-muted-foreground">Valores de tu compensación variable por indicador</p>
                </div>

                {/* Card colaborador + filtro */}
                <div className="rounded-2xl border border-sidebar-border/70 bg-card shadow-sm">
                    <div className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">

                        {/* Avatar */}
                        <div className="flex items-center gap-3">
                            <div className="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-700 sm:size-14">
                                {colaborador.imagen ? (
                                    <img src={`/storage/${colaborador.imagen}`} alt={colaborador.nombre_completo}
                                        className="size-full object-cover" onError={(e) => { (e.currentTarget as HTMLImageElement).style.display = 'none'; }} />
                                ) : <User className="size-6 text-white sm:size-7" />}
                            </div>
                            <div className="min-w-0">
                                <p className="truncate font-bold text-foreground">{colaborador.nombre_completo}</p>
                                <p className="truncate text-xs text-blue-600 dark:text-blue-400">{colaborador.cargo}{colaborador.area ? ` · ${colaborador.area}` : ''}</p>
                            </div>
                        </div>

                        {/* Selector de mes */}
                        <div className="flex items-end gap-2">
                            <div className="flex flex-1 flex-col gap-1 sm:flex-none">
                                <label className="text-[10px] font-semibold uppercase text-muted-foreground">Mes</label>
                                <select
                                    value={mesActual}
                                    onChange={(e) => setMes(e.target.value)}
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 sm:w-auto"
                                >
                                    <option value="">Todos los meses</option>
                                    {meses.map((m) => (
                                        <option key={m} value={m}>{m}</option>
                                    ))}
                                </select>
                            </div>
                            {mesActual && (
                                <button onClick={() => setMes('')} className="flex h-9 shrink-0 items-center gap-1 rounded-md border border-input bg-background px-3 text-sm text-muted-foreground hover:text-foreground">
                                    <X className="size-3.5" />
                                    <span className="hidden sm:inline">Limpiar</span>
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Sin datos */}
                {incentivos.length === 0 && (
                    <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 py-16 text-center dark:border-slate-700">
                        <TrendingUp className="size-12 text-slate-300 dark:text-slate-600" />
                        <p className="mt-4 text-base font-medium text-muted-foreground">
                            {!mesActual ? 'Aún no tienes registros de variable.' : 'Sin registros para el mes seleccionado.'}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground/60">Los datos los carga el equipo de Gente.</p>
                    </div>
                )}

                {/* Registros */}
                {incentivos.length > 0 && (
                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <DollarSign className="size-5 text-blue-600" />
                            <h2 className="text-base font-bold text-foreground">
                                Registros <span className="ml-1 text-sm font-normal text-muted-foreground">({incentivos.length})</span>
                            </h2>
                        </div>
                        {incentivos.map((item) => (
                            <RegistroCard key={item.id} item={item} />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
