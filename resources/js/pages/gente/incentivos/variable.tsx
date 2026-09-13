import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronDown, Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Legend,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Gente', href: '/modules/gente' },
    { title: 'Incentivos', href: '/modules/gente/incentivos' },
    { title: 'Variable', href: '/modules/gente/incentivos/variable' },
];

interface ColaboradorResumen {
    id: number;
    nombres: string;
    apellidos: string;
    cedula: string;
}

interface IncentivoRow {
    id: number;
    mes: string | null;
    cedula: string | null;
    nombre: string | null;
    cargo: string | null;
    indicador_1: string | null;
    pilar_1: string | null;
    indicador_2: string | null;
    pilar_2: string | null;
    indicador_3: string | null;
    pilar_3: string | null;
    valor_indicador_1: string | null;
    valor_indicador_2: string | null;
    valor_indicador_3: string | null;
    total_4: string | null;
    meta_4: string | null;
    colaborador: ColaboradorResumen | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface IncentivosPaginator {
    data: IncentivoRow[];
    links: PaginationLink[];
    total: number;
}

interface Filtros {
    desde: string;
    hasta: string;
    colaborador: string;
    cargo: string;
}

interface Opciones {
    cargos: string[];
}

// ── Helpers ────────────────────────────────────────────────────────────────

function fmtMoney(v: string | null | undefined): string {
    if (!v || v === '') return '—';
    const n = parseFloat(v);
    if (isNaN(n)) return v;
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(n);
}

function pct(total: string | null, meta: string | null): number {
    const t = parseFloat(total ?? ''), m = parseFloat(meta ?? '');
    if (isNaN(t) || isNaN(m) || m === 0) return 0;
    return Math.min(Math.round((t / m) * 100), 100);
}

function cumple(total: string | null, meta: string | null): boolean | null {
    const t = parseFloat(total ?? ''), m = parseFloat(meta ?? '');
    if (isNaN(t) || isNaN(m)) return null;
    return t >= m;
}

// ── Barra de progreso ──────────────────────────────────────────────────────

function BarraProgreso({ total, meta }: { total: string | null; meta: string | null }) {
    const p  = pct(total, meta);
    const ok = cumple(total, meta);
    const color = ok === true ? 'bg-emerald-500' : ok === false ? 'bg-blue-500' : 'bg-slate-300';

    return (
        <div className="flex items-center gap-2 min-w-[140px]">
            <div className="flex-1 h-2 overflow-hidden rounded-full bg-slate-100">
                <div
                    className={`h-full rounded-full transition-all duration-500 ${color}`}
                    style={{ width: `${p}%` }}
                />
            </div>
            <span className={`text-xs font-bold tabular-nums w-9 text-right ${
                ok === true ? 'text-emerald-600' : ok === false ? 'text-blue-600' : 'text-slate-400'
            }`}>{p}%</span>
        </div>
    );
}

// ── SearchSelect ───────────────────────────────────────────────────────────

function SearchSelect({ placeholder, value, options, onChange }: {
    placeholder: string; value: string; options: string[]; onChange: (v: string) => void;
}) {
    const [query, setQuery] = useState(value);
    const [open, setOpen]   = useState(false);
    const ref               = useRef<HTMLDivElement>(null);

    useEffect(() => { setQuery(value); }, [value]);
    useEffect(() => {
        const fn = (e: MouseEvent) => { if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false); };
        document.addEventListener('mousedown', fn);
        return () => document.removeEventListener('mousedown', fn);
    }, []);

    const filtered = options.filter((o) => o.toLowerCase().includes(query.toLowerCase()));

    return (
        <div ref={ref} className="relative">
            <div className="relative flex items-center">
                <Search className="pointer-events-none absolute left-3 size-4 text-slate-400" />
                <input
                    className="h-10 w-full rounded-md border border-input bg-background pl-9 pr-8 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                    placeholder={placeholder}
                    value={query}
                    onChange={(e) => { setQuery(e.target.value); onChange(e.target.value); setOpen(true); }}
                    onFocus={() => setOpen(true)}
                />
                {query ? (
                    <button onClick={() => { setQuery(''); onChange(''); setOpen(false); }} className="absolute right-2 text-slate-400 hover:text-slate-600">
                        <X className="size-4" />
                    </button>
                ) : (
                    <ChevronDown className="pointer-events-none absolute right-2 size-4 text-slate-400" />
                )}
            </div>
            {open && filtered.length > 0 && (
                <ul className="absolute z-50 mt-1 max-h-52 w-full overflow-auto rounded-md border border-input bg-white shadow-lg text-sm dark:bg-slate-900">
                    {filtered.map((opt) => (
                        <li key={opt} onMouseDown={() => { setQuery(opt); onChange(opt); setOpen(false); }}
                            className={`cursor-pointer px-3 py-2 hover:bg-blue-50 hover:text-blue-700 dark:hover:bg-blue-900/30 ${opt === value ? 'bg-blue-50 font-medium text-blue-700' : ''}`}>
                            {opt}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

// ── Gráfica de barras agrupadas por mes ───────────────────────────────────

function fmt(v: number) {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(v);
}

function GraficaVariableMensual({ data }: { data: IncentivoRow[] }) {
    // Agrupar por mes: sumar valor_indicador_1/2/3
    const porMes = data.reduce<Record<string, { mes: string; v1: number; v2: number; v3: number }>>((acc, item) => {
        const mes = item.mes ?? 'Sin mes';
        if (!acc[mes]) acc[mes] = { mes, v1: 0, v2: 0, v3: 0 };
        const v1 = parseFloat(item.valor_indicador_1 ?? '');
        const v2 = parseFloat(item.valor_indicador_2 ?? '');
        const v3 = parseFloat(item.valor_indicador_3 ?? '');
        if (!isNaN(v1)) acc[mes].v1 += v1;
        if (!isNaN(v2)) acc[mes].v2 += v2;
        if (!isNaN(v3)) acc[mes].v3 += v3;
        return acc;
    }, {});

    const chartData = Object.values(porMes);
    if (chartData.length === 0) return null;

    return (
        <div className="rounded-xl border border-sidebar-border/70 bg-card p-5 space-y-3">
            <h3 className="text-sm font-bold text-foreground">Valores por indicador — mensual</h3>
            <ResponsiveContainer width="100%" height={280}>
                <BarChart data={chartData} margin={{ top: 4, right: 16, left: 16, bottom: 4 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke="currentColor" strokeOpacity={0.08} />
                    <XAxis dataKey="mes" tick={{ fontSize: 12 }} />
                    <YAxis
                        tickFormatter={(v) => new Intl.NumberFormat('es-CO', { notation: 'compact', currency: 'COP', style: 'currency', maximumFractionDigits: 0 }).format(v)}
                        tick={{ fontSize: 11 }}
                        width={72}
                    />
                    <Tooltip
                        formatter={(value) => [fmt(Number(value ?? 0)), '']}
                        contentStyle={{ fontSize: 12, borderRadius: 8 }}
                    />
                    <Legend wrapperStyle={{ fontSize: 12 }} />
                    <Bar dataKey="v1" name="Valor Ind. 1" fill="#3b82f6" radius={[4, 4, 0, 0]} />
                    <Bar dataKey="v2" name="Valor Ind. 2" fill="#10b981" radius={[4, 4, 0, 0]} />
                    <Bar dataKey="v3" name="Valor Ind. 3" fill="#f59e0b" radius={[4, 4, 0, 0]} />
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}

// ── Panel de indicadores mensuales (encima de la tabla) ───────────────────

function ResumenMensual({ data }: { data: IncentivoRow[] }) {
    // Agrupar por mes y sumar totales y metas
    const porMes = data.reduce<Record<string, { total: number; meta: number; count: number }>>((acc, item) => {
        const mes = item.mes ?? 'Sin mes';
        const t   = parseFloat(item.total_4 ?? '');
        const m   = parseFloat(item.meta_4  ?? '');
        if (!acc[mes]) acc[mes] = { total: 0, meta: 0, count: 0 };
        if (!isNaN(t)) acc[mes].total += t;
        if (!isNaN(m)) acc[mes].meta  += m;
        acc[mes].count++;
        return acc;
    }, {});

    const meses = Object.entries(porMes);
    if (meses.length === 0) return null;

    return (
        <div className="rounded-xl border border-sidebar-border/70 bg-card p-5 space-y-4">
            <h3 className="text-sm font-bold text-foreground">Indicador mensual — Total vs Meta</h3>
            <div className="space-y-3">
                {meses.map(([mes, { total, meta, count }]) => {
                    const p  = meta > 0 ? Math.min(Math.round((total / meta) * 100), 100) : 0;
                    const ok = meta > 0 ? total >= meta : null;
                    const barColor = ok === true ? 'bg-emerald-500' : ok === false ? 'bg-blue-500' : 'bg-slate-300';
                    const txtColor = ok === true ? 'text-emerald-600' : ok === false ? 'text-blue-600' : 'text-slate-400';
                    return (
                        <div key={mes} className="space-y-1">
                            <div className="flex items-center justify-between text-xs">
                                <span className="font-semibold text-foreground">{mes}
                                    <span className="ml-1.5 font-normal text-muted-foreground">({count} reg.)</span>
                                </span>
                                <div className="flex items-center gap-3 tabular-nums">
                                    <span className={`font-bold ${txtColor}`}>
                                        {new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(total)}
                                    </span>
                                    <span className="text-muted-foreground">
                                        / {new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(meta)}
                                    </span>
                                    <span className={`w-10 text-right font-bold ${txtColor}`}>{p}%</span>
                                </div>
                            </div>
                            <div className="h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div
                                    className={`h-full rounded-full transition-all duration-700 ${barColor}`}
                                    style={{ width: `${p}%` }}
                                />
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

function MobileCard({ item }: { item: IncentivoRow }) {
    const nombre = item.colaborador
        ? `${item.colaborador.nombres} ${item.colaborador.apellidos}`
        : (item.nombre ?? '—');
    const ok = cumple(item.total_4, item.meta_4);
    const p  = pct(item.total_4, item.meta_4);

    return (
        <div className="rounded-xl border border-slate-100 bg-white shadow-sm overflow-hidden p-4 space-y-3">
            {/* Cabecera */}
            <div className="flex items-start justify-between gap-2">
                <div>
                    <p className="font-semibold text-slate-800 text-sm">{nombre}</p>
                    <p className="text-xs text-slate-400">{item.cargo ?? ''} · {item.mes ?? ''}</p>
                </div>
                <span className={`shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold ${
                    ok === true ? 'bg-emerald-100 text-emerald-700' : ok === false ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'
                }`}>
                    {fmtMoney(item.total_4)}
                </span>
            </div>

            {/* Barra total vs meta */}
            {(item.total_4 || item.meta_4) && (
                <div className="space-y-1">
                    <div className="flex justify-between text-[10px] text-slate-400">
                        <span>Total vs Meta</span>
                        <span>{fmtMoney(item.meta_4)}</span>
                    </div>
                    <BarraProgreso total={item.total_4} meta={item.meta_4} />
                    <p className="text-right text-[10px] text-slate-400">{p}%</p>
                </div>
            )}

            {/* Indicadores */}
            <div className="grid grid-cols-3 gap-2 text-xs">
                {[
                    { ind: item.indicador_1, pilar: item.pilar_1, val: item.valor_indicador_1 },
                    { ind: item.indicador_2, pilar: item.pilar_2, val: item.valor_indicador_2 },
                    { ind: item.indicador_3, pilar: item.pilar_3, val: item.valor_indicador_3 },
                ].map((i, idx) => i.val && (
                    <div key={idx} className="rounded-lg bg-slate-50 p-2 space-y-0.5">
                        <p className="text-[10px] font-medium text-slate-500 truncate">{i.pilar ?? `Ind. ${idx + 1}`}</p>
                        <p className="font-bold text-slate-700">{fmtMoney(i.val)}</p>
                        {i.ind && <p className="text-[10px] text-slate-400 truncate">{i.ind}</p>}
                    </div>
                ))}
            </div>
        </div>
    );
}

// ── Componente principal ───────────────────────────────────────────────────

export default function IncentivosVariable({
    incentivos,
    filters,
    opciones,
}: {
    incentivos: IncentivosPaginator;
    filters: Filtros;
    opciones: Opciones;
}) {
    const [form, setForm] = useState<Filtros>({
        desde:       filters.desde       ?? '',
        hasta:       filters.hasta       ?? '',
        colaborador: filters.colaborador ?? '',
        cargo:       filters.cargo       ?? '',
    });

    const isFirst       = useRef(true);
    const debouncedForm = useDebouncedValue(form, 300);

    useEffect(() => {
        if (isFirst.current) { isFirst.current = false; return; }
        router.get(route('gente.incentivos.variable'), debouncedForm as unknown as Record<string, string>, { preserveState: true, replace: true });
    }, [JSON.stringify(debouncedForm)]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Variable — Incentivos" />
            <div className="flex h-full flex-1 flex-col gap-4 p-3 sm:gap-6 sm:p-4">

                {/* Encabezado */}
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <HeadingSmall
                            title="Variable"
                            description="Valores de compensación variable por colaborador (Valor Ind. 1, 2, 3 · Total 4 vs Meta 4)."
                        />
                    </div>
                </div>

                {/* Filtros */}
                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end">
                    <div className="flex flex-col gap-1">
                        <label className="text-[10px] font-semibold uppercase text-muted-foreground">Desde</label>
                        <input type="date" value={form.desde}
                            onChange={(e) => setForm({ ...form, desde: e.target.value })}
                            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                    <div className="flex flex-col gap-1">
                        <label className="text-[10px] font-semibold uppercase text-muted-foreground">Hasta</label>
                        <input type="date" value={form.hasta}
                            onChange={(e) => setForm({ ...form, hasta: e.target.value })}
                            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                    </div>
                    <div className="flex flex-col gap-1 sm:col-span-2 lg:col-span-1 lg:min-w-[200px]">
                        <label className="text-[10px] font-semibold uppercase text-muted-foreground">Colaborador</label>
                        <Input placeholder="Nombre o identificación" value={form.colaborador}
                            onChange={(e) => setForm({ ...form, colaborador: e.target.value })}
                            className="h-9" />
                    </div>
                    <div className="flex flex-col gap-1 sm:col-span-2 lg:col-span-1 lg:min-w-[180px]">
                        <label className="text-[10px] font-semibold uppercase text-muted-foreground">Cargo</label>
                        <SearchSelect placeholder="Cargo" value={form.cargo} options={opciones.cargos}
                            onChange={(v) => setForm({ ...form, cargo: v })} />
                    </div>
                    {(form.desde || form.hasta || form.colaborador || form.cargo) && (
                        <button onClick={() => setForm({ desde: '', hasta: '', colaborador: '', cargo: '' })}
                            className="flex h-9 items-center gap-1 self-end rounded-md border border-input bg-background px-3 text-sm text-muted-foreground hover:text-foreground sm:col-span-2 lg:col-span-1">
                            <X className="size-3.5" /> Limpiar
                        </button>
                    )}
                </div>

                {/* Contador */}
                <p className="text-xs text-muted-foreground">{incentivos.total} registro(s)</p>

                {incentivos.data.length === 0 ? (
                    <p className="py-10 text-center text-sm text-muted-foreground">
                        No hay registros que coincidan con los filtros.
                    </p>
                ) : (
                    <>
                        {/* Gráfica de barras por mes */}
                        <GraficaVariableMensual data={incentivos.data} />

                        {/* Panel de indicadores mensuales */}
                        <ResumenMensual data={incentivos.data} />

                        {/* Móvil */}
                        <div className="flex flex-col gap-3 md:hidden">
                            {incentivos.data.map((item) => <MobileCard key={item.id} item={item} />)}
                        </div>

                        {/* Desktop */}
                        <div className="hidden md:block overflow-x-auto rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="whitespace-nowrap">Mes</TableHead>
                                        <TableHead className="whitespace-nowrap">Colaborador</TableHead>
                                        <TableHead className="whitespace-nowrap">Cargo</TableHead>
                                        <TableHead className="whitespace-nowrap">Pilar 1</TableHead>
                                        <TableHead className="whitespace-nowrap">Pilar 2</TableHead>
                                        <TableHead className="whitespace-nowrap">Pilar 3</TableHead>
                                        <TableHead className="whitespace-nowrap text-right">Valor Ind. 1</TableHead>
                                        <TableHead className="whitespace-nowrap text-right">Valor Ind. 2</TableHead>
                                        <TableHead className="whitespace-nowrap text-right">Valor Ind. 3</TableHead>
                                        <TableHead className="whitespace-nowrap text-right">Total 4</TableHead>
                                        <TableHead className="whitespace-nowrap text-right">Meta 4</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {incentivos.data.map((item) => {
                                        const nombre = item.colaborador
                                            ? `${item.colaborador.nombres} ${item.colaborador.apellidos}`
                                            : (item.nombre ?? '—');
                                        const ok = cumple(item.total_4, item.meta_4);
                                        return (
                                            <TableRow key={item.id}>
                                                <TableCell className="whitespace-nowrap">{item.mes ?? '—'}</TableCell>
                                                <TableCell>
                                                    <p className="whitespace-nowrap font-medium">{nombre}</p>
                                                    <p className="text-xs text-muted-foreground">{item.cedula ?? item.colaborador?.cedula ?? ''}</p>
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">{item.cargo ?? '—'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{item.pilar_1 ?? '—'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{item.pilar_2 ?? '—'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{item.pilar_3 ?? '—'}</TableCell>
                                                <TableCell className="text-right tabular-nums">{fmtMoney(item.valor_indicador_1)}</TableCell>
                                                <TableCell className="text-right tabular-nums">{fmtMoney(item.valor_indicador_2)}</TableCell>
                                                <TableCell className="text-right tabular-nums">{fmtMoney(item.valor_indicador_3)}</TableCell>
                                                <TableCell className={`text-right tabular-nums font-semibold ${ok === true ? 'text-emerald-600' : ok === false ? 'text-blue-600' : ''}`}>
                                                    {fmtMoney(item.total_4)}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">{fmtMoney(item.meta_4)}</TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </div>
                    </>
                )}

                {/* Paginación */}
                {incentivos.links.length > 3 && (
                    <div className="flex flex-wrap gap-1">
                        {incentivos.links.map((link, index) => (
                            <Button key={index} variant={link.active ? 'default' : 'outline'} size="sm"
                                disabled={!link.url} asChild={!!link.url}>
                                {link.url ? (
                                    <Link href={link.url} preserveScroll dangerouslySetInnerHTML={{ __html: link.label }} />
                                ) : (
                                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
