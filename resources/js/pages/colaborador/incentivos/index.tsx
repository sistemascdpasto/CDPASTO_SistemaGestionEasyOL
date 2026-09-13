import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Award, Gift, Search, Star, Trophy, User, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mis Incentivos', href: '/portal/mis-incentivos' },
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
    cargo: string | null;
    indicador_1: string | null; pilar_1: string | null; total_1: string | null; meta_1: string | null;
    indicador_2: string | null; pilar_2: string | null; total_2: string | null; meta_2: string | null;
    indicador_3: string | null; pilar_3: string | null; total_3: string | null; meta_3: string | null;
    podium: string | null;
    created_at: string;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function fmt(v: string | null | undefined): string {
    if (!v) return '—';
    const n = parseFloat(v);
    return isNaN(n) ? v : n.toLocaleString('es-CO', { maximumFractionDigits: 2 });
}

function cumple(total: string | null, meta: string | null): boolean | null {
    const t = parseFloat(total ?? ''), m = parseFloat(meta ?? '');
    if (isNaN(t) || isNaN(m)) return null;
    return t >= m;
}

function pct(total: string | null, meta: string | null): number | null {
    const t = parseFloat(total ?? ''), m = parseFloat(meta ?? '');
    if (isNaN(t) || isNaN(m) || m === 0) return null;
    return Math.round((t / m) * 100);
}

// ── Barra animada ─────────────────────────────────────────────────────────────

function Barra({ p, ok }: { p: number; ok: boolean | null }) {
    const [w, setW] = useState(0);
    useEffect(() => { const t = setTimeout(() => setW(Math.min(p, 100)), 200); return () => clearTimeout(t); }, [p]);
    const color = ok === true ? 'bg-green-500' : ok === false ? 'bg-red-400' : 'bg-slate-300';
    return (
        <div className="h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
            <div className={`h-full rounded-full transition-all duration-700 ${color}`} style={{ width: `${w}%` }} />
        </div>
    );
}

// ── Tarjeta de un indicador ───────────────────────────────────────────────────

function TarjetaIndicador({ nombre, pilar, total, meta, num }: {
    nombre: string | null; pilar: string | null; total: string | null; meta: string | null; num: number;
}) {
    if (!nombre && !total && !meta) return null;
    const ok = cumple(total, meta);
    const p  = pct(total, meta) ?? 0;
    const border = ok === true ? 'border-green-200 bg-green-50 dark:border-green-800/50 dark:bg-green-900/20'
        : ok === false ? 'border-red-200 bg-red-50 dark:border-red-800/50 dark:bg-red-900/20'
        : 'border-slate-200 bg-slate-50';
    const valColor = ok === true ? 'text-green-700 dark:text-green-400' : ok === false ? 'text-red-600 dark:text-red-400' : 'text-slate-400';
    return (
        <div className={`rounded-xl border p-4 ${border}`}>
            <div className="mb-2 flex items-start justify-between gap-2">
                <div>
                    {pilar && <span className="mb-0.5 inline-block rounded-full bg-white/60 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-400">{pilar}</span>}
                    <p className="text-xs font-bold text-slate-800 dark:text-slate-100">{nombre ?? `Indicador ${num}`}</p>
                </div>
                <span className={`shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold ${ok === null ? 'bg-slate-100 text-slate-400' : ok ? 'bg-green-700 text-white' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'}`}>
                    {ok === null ? 'Sin dato' : ok ? '✓ Cumple' : '✗ No cumple'}
                </span>
            </div>
            <div className="mb-1 flex items-baseline gap-1">
                <span className={`text-xl font-black tabular-nums ${valColor}`}>{fmt(total)}</span>
                <span className="text-[10px] text-slate-400">/ meta {fmt(meta)}</span>
            </div>
            <Barra p={p} ok={ok} />
            <p className="mt-0.5 text-right text-[10px] text-slate-400">{p}%</p>
        </div>
    );
}

// ── Card de un registro ───────────────────────────────────────────────────────

function RegistroCard({ item }: { item: IncentivoRow }) {
    const indicadores = [
        { nombre: item.indicador_1, pilar: item.pilar_1, total: item.total_1, meta: item.meta_1 },
        { nombre: item.indicador_2, pilar: item.pilar_2, total: item.total_2, meta: item.meta_2 },
        { nombre: item.indicador_3, pilar: item.pilar_3, total: item.total_3, meta: item.meta_3 },
    ].filter(i => i.nombre || i.total || i.meta);

    const cumplidos = indicadores.filter(i => cumple(i.total, i.meta) === true).length;
    const sumaTotal = indicadores.reduce((a, i) => a + (parseFloat(i.total ?? '') || 0), 0);
    const sumaMeta  = indicadores.reduce((a, i) => a + (parseFloat(i.meta ?? '') || 0), 0);
    const okTotal   = sumaMeta > 0 ? sumaTotal >= sumaMeta : null;

    return (
        <div className="rounded-2xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border overflow-hidden">
            {/* Encabezado del registro */}
            <div className="flex items-center justify-between gap-3 border-b border-sidebar-border/50 bg-muted/30 px-4 py-3">
                <div className="flex items-center gap-2">
                    <span className="text-sm font-bold text-foreground">{item.mes ?? '—'}</span>
                    {item.podium && (
                        <span className="flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:border-amber-800/50 dark:bg-amber-900/20 dark:text-amber-400">
                            <Trophy className="size-3" /> {item.podium}
                        </span>
                    )}
                </div>
                <div className="flex items-center gap-2">
                    {/* Estrellas */}
                    <div className="flex gap-0.5">
                        {Array.from({ length: indicadores.length }).map((_, i) => (
                            <Star key={i} className={`size-3.5 ${i < cumplidos ? 'fill-amber-400 text-amber-400' : 'text-slate-300 dark:text-slate-600'}`} />
                        ))}
                    </div>
                    {/* Total calculado */}
                    {sumaMeta > 0 && (
                        <span className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${okTotal ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'}`}>
                            {sumaTotal.toLocaleString('es-CO', { maximumFractionDigits: 1 })} / {sumaMeta.toLocaleString('es-CO', { maximumFractionDigits: 1 })}
                        </span>
                    )}
                </div>
            </div>
            {/* Indicadores */}
            <div className="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                {indicadores.map((ind, i) => (
                    <TarjetaIndicador key={i} num={i + 1} nombre={ind.nombre} pilar={ind.pilar} total={ind.total} meta={ind.meta} />
                ))}
            </div>
        </div>
    );
}

// ── Página ────────────────────────────────────────────────────────────────────

export default function MisIncentivos({
    colaborador, incentivos, desde, hasta,
}: {
    colaborador: ColaboradorInfo;
    incentivos: IncentivoRow[];
    desde: string;
    hasta: string;
}) {
    const [form, setForm] = useState({ desde, hasta });
    const isFirst = useRef(true);

    useEffect(() => {
        if (isFirst.current) { isFirst.current = false; return; }
        router.get(route('portal.mis-incentivos'), form, { preserveState: true, replace: true });
    }, [form.desde, form.hasta]);

    const limpiar = () => setForm({ desde: '', hasta: '' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mis Incentivos" />
            <div className="flex flex-col gap-5 px-4 pb-10 pt-4 sm:px-6">

                {/* Título */}
                <div>
                    <h1 className="text-2xl font-bold text-foreground sm:text-3xl">Mis Incentivos</h1>
                    <p className="mt-0.5 text-sm text-muted-foreground">Tus indicadores de incentivo y su cumplimiento de meta</p>
                </div>

                {/* Card colaborador + filtro de fechas */}
                <div className="rounded-2xl border border-sidebar-border/70 bg-card shadow-sm">
                    <div className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">

                        {/* Avatar */}
                        <div className="flex items-center gap-4">
                            <div className="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-amber-600">
                                {colaborador.imagen ? (
                                    <img src={`/storage/${colaborador.imagen}`} alt={colaborador.nombre_completo}
                                        className="size-full object-cover" onError={(e) => { (e.currentTarget as HTMLImageElement).style.display = 'none'; }} />
                                ) : <User className="size-7 text-white" />}
                            </div>
                            <div>
                                <p className="font-bold text-foreground">{colaborador.nombre_completo}</p>
                                <p className="text-xs text-amber-600 dark:text-amber-400">{colaborador.cargo}{colaborador.area ? ` · ${colaborador.area}` : ''}</p>
                            </div>
                        </div>

                        {/* Filtro desde/hasta */}
                        <div className="flex flex-wrap items-end gap-2">
                            <div className="flex flex-col gap-1">
                                <label className="text-[10px] font-semibold uppercase text-muted-foreground">Desde</label>
                                <input
                                    type="date"
                                    value={form.desde}
                                    onChange={(e) => setForm({ ...form, desde: e.target.value })}
                                    className="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                />
                            </div>
                            <div className="flex flex-col gap-1">
                                <label className="text-[10px] font-semibold uppercase text-muted-foreground">Hasta</label>
                                <input
                                    type="date"
                                    value={form.hasta}
                                    onChange={(e) => setForm({ ...form, hasta: e.target.value })}
                                    className="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                />
                            </div>
                            {(form.desde || form.hasta) && (
                                <button onClick={limpiar} className="flex h-9 items-center gap-1 rounded-md border border-input bg-background px-3 text-sm text-muted-foreground hover:text-foreground">
                                    <X className="size-3.5" /> Limpiar
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Sin datos */}
                {incentivos.length === 0 && (
                    <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 py-16 text-center dark:border-slate-700">
                        <Gift className="size-12 text-slate-300 dark:text-slate-600" />
                        <p className="mt-4 text-base font-medium text-muted-foreground">
                            {!form.desde && !form.hasta ? 'Aún no tienes registros de incentivos.' : 'Sin registros en el rango seleccionado.'}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground/60">Los datos los carga el equipo de Gente.</p>
                    </div>
                )}

                {/* Lista de registros */}
                {incentivos.length > 0 && (
                    <div className="space-y-4">
                        <div className="flex items-center gap-2">
                            <Award className="size-5 text-amber-500" />
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
