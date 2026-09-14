import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import AppLayout from '@/layouts/app-layout';
import { ImportarIncentivosDialog } from '@/pages/gente/incentivos/importar-dialog';
import { PodiumIncentivos } from '@/pages/gente/incentivos/podium';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronDown, Search, Upload, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Gente', href: '/modules/gente' },
    { title: 'Incentivos', href: '/modules/gente/incentivos' },
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
    total_1: string | null;
    meta_1: string | null;
    indicador_2: string | null;
    pilar_2: string | null;
    total_2: string | null;
    meta_2: string | null;
    indicador_3: string | null;
    pilar_3: string | null;
    total_3: string | null;
    meta_3: string | null;
    podium: string | null;
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
    mes: string;
    colaborador: string;
    cargo: string;
}

interface Opciones {
    meses: string[];
    cargos: string[];
}

// ── SearchSelect con búsqueda inmediata ────────────────────────────────────
function SearchSelect({
    placeholder,
    value,
    options,
    onChange,
}: {
    placeholder: string;
    value: string;
    options: string[];
    onChange: (v: string) => void;
}) {
    const [query, setQuery] = useState(value);
    const [open, setOpen]   = useState(false);
    const ref               = useRef<HTMLDivElement>(null);

    useEffect(() => { setQuery(value); }, [value]);

    useEffect(() => {
        const fn = (e: MouseEvent) => {
            if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
        };
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
                        <li
                            key={opt}
                            onMouseDown={() => { setQuery(opt); onChange(opt); setOpen(false); }}
                            className={`cursor-pointer px-3 py-2 hover:bg-blue-50 hover:text-blue-700 dark:hover:bg-blue-900/30
                                ${opt === value ? 'bg-blue-50 font-medium text-blue-700 dark:bg-blue-900/30' : ''}`}
                        >
                            {opt}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function fmtDecimal(valor: string | null | undefined): string {
    if (!valor || valor === '') return '—';
    const n = parseFloat(valor);
    return isNaN(n) ? valor : n.toLocaleString('es-CO', { maximumFractionDigits: 2 });
}

// ── Tarjeta mobile para un registro ───────────────────────────────────────
function MobileCard({ item }: { item: IncentivoRow }) {
    const [open, setOpen] = useState(false);
    const nombre = item.colaborador
        ? `${item.colaborador.nombres} ${item.colaborador.apellidos}`
        : (item.nombre ?? '—');
    const cedula = item.cedula ?? item.colaborador?.cedula ?? '';

    return (
        <div className="rounded-xl border border-slate-100 bg-white shadow-sm overflow-hidden">
            {/* Cabecera siempre visible */}
            <button
                className="w-full flex items-center justify-between gap-3 px-4 py-3 text-left"
                onClick={() => setOpen(!open)}
            >
                <div className="min-w-0">
                    <p className="font-semibold text-slate-800 text-sm truncate">{nombre}</p>
                    <p className="text-xs text-slate-400">{cedula} · {item.cargo ?? ''} · {item.mes ?? ''}</p>
                </div>
                <div className="flex items-center gap-2 shrink-0">
                    {item.total_4 && (
                        <span className="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700">
                            {fmtDecimal(item.total_4)} pts
                        </span>
                    )}
                    <ChevronDown className={`size-4 text-slate-400 transition-transform ${open ? 'rotate-180' : ''}`} />
                </div>
            </button>

            {/* Detalle expandible */}
            {open && (
                <div className="border-t border-slate-100 px-4 py-3 grid grid-cols-2 gap-3 text-xs">
                    {[
                        { label: item.indicador_1, pilar: item.pilar_1, total: item.total_1, meta: item.meta_1 },
                        { label: item.indicador_2, pilar: item.pilar_2, total: item.total_2, meta: item.meta_2 },
                        { label: item.indicador_3, pilar: item.pilar_3, total: item.total_3, meta: item.meta_3 },
                    ].map((ind, i) => ind.label && (
                        <div key={i} className="rounded-lg bg-slate-50 p-2">
                            <p className="font-medium text-slate-600 truncate mb-0.5">{ind.label}</p>
                            {ind.pilar && <span className="inline-block rounded-full bg-blue-100 px-1.5 text-[10px] text-blue-700">{ind.pilar}</span>}
                            <div className="flex justify-between mt-1">
                                <span className="font-bold text-slate-700">{fmtDecimal(ind.total)}</span>
                                <span className="text-slate-400">Meta: {fmtDecimal(ind.meta)}</span>
                            </div>
                        </div>
                    ))}
                    {item.podium && (
                        <div className="col-span-2 text-center text-slate-500">
                            Pódium: <span className="font-semibold text-blue-600">{item.podium}</span>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

// ── Componente principal ───────────────────────────────────────────────────
export default function IncentivosIndex({
    incentivos,
    filters,
    opciones,
}: {
    incentivos: IncentivosPaginator;
    filters: Filtros;
    opciones: Opciones;
}) {
    const [form, setForm] = useState<Filtros>({
        mes:         filters.mes         ?? '',
        colaborador: filters.colaborador ?? '',
        cargo:       filters.cargo       ?? '',
    });

    const isFirst     = useRef(true);
    const debouncedForm = useDebouncedValue(form, 300);

    useEffect(() => {
        if (isFirst.current) { isFirst.current = false; return; }
        router.get(route('gente.incentivos.index'), debouncedForm, { preserveState: true, replace: true });
    }, [JSON.stringify(debouncedForm)]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Incentivos" />
            <div className="flex h-full flex-1 flex-col gap-4 p-3 sm:gap-6 sm:p-4">

                {/* Encabezado */}
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <HeadingSmall
                        title="Incentivos"
                        description="Registros de indicadores e incentivos importados desde Excel."
                    />
                    <ImportarIncentivosDialog
                        trigger={
                            <Button variant="outline" size="sm">
                                <Upload className="size-4" />
                                <span className="hidden sm:inline">Importar Excel</span>
                                <span className="sm:hidden">Importar</span>
                            </Button>
                        }
                    />
                </div>

                {/* Filtros */}
                <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <SearchSelect
                        placeholder="Mes"
                        value={form.mes}
                        options={opciones.meses}
                        onChange={(v) => setForm({ ...form, mes: v })}
                    />
                    <Input
                        placeholder="Nombre o identificación"
                        value={form.colaborador}
                        onChange={(e) => setForm({ ...form, colaborador: e.target.value })}
                    />
                    <SearchSelect
                        placeholder="Cargo"
                        value={form.cargo}
                        options={opciones.cargos}
                        onChange={(v) => setForm({ ...form, cargo: v })}
                    />
                </div>

                {/* Pódium */}
                {incentivos.data.length > 0 && (
                    <PodiumIncentivos incentivos={incentivos.data} />
                )}

                {/* Tabla — solo desktop (md+), tarjetas en móvil */}
                {incentivos.data.length === 0 ? (
                    <p className="py-10 text-center text-sm text-muted-foreground">
                        No hay registros de incentivos que coincidan con los filtros.
                    </p>
                ) : (
                    <>
                        {/* Vista móvil: tarjetas colapsables */}
                        <div className="flex flex-col gap-2 md:hidden">
                            {incentivos.data.map((item) => (
                                <MobileCard key={item.id} item={item} />
                            ))}
                        </div>

                        {/* Vista desktop: tabla con scroll horizontal */}
                        <div className="hidden md:block overflow-x-auto rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="whitespace-nowrap">Mes</TableHead>
                                        <TableHead className="whitespace-nowrap">Colaborador</TableHead>
                                        <TableHead className="whitespace-nowrap">Cargo</TableHead>
                                        <TableHead className="whitespace-nowrap">Indicador 1</TableHead>
                                        <TableHead className="whitespace-nowrap">Pilar 1</TableHead>
                                        <TableHead className="whitespace-nowrap text-right">Total 1</TableHead>
                                        <TableHead className="whitespace-nowrap text-right">Meta 1</TableHead>
                                        <TableHead className="whitespace-nowrap">Indicador 2</TableHead>
                                        <TableHead className="whitespace-nowrap">Pilar 2</TableHead>
                                        <TableHead className="whitespace-nowrap text-right">Total 2</TableHead>
                                        <TableHead className="whitespace-nowrap text-right">Meta 2</TableHead>
                                        <TableHead className="whitespace-nowrap">Indicador 3</TableHead>
                                        <TableHead className="whitespace-nowrap">Pilar 3</TableHead>
                                        <TableHead className="whitespace-nowrap text-right">Total 3</TableHead>
                                        <TableHead className="whitespace-nowrap text-right">Meta 3</TableHead>
                                        <TableHead className="whitespace-nowrap">Podium</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {incentivos.data.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="whitespace-nowrap">{item.mes ?? '—'}</TableCell>
                                            <TableCell>
                                                <p className="whitespace-nowrap font-medium">
                                                    {item.colaborador
                                                        ? `${item.colaborador.nombres} ${item.colaborador.apellidos}`
                                                        : (item.nombre ?? '—')}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {item.cedula ?? item.colaborador?.cedula ?? ''}
                                                </p>
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">{item.cargo ?? '—'}</TableCell>
                                            <TableCell className="max-w-[160px] truncate" title={item.indicador_1 ?? ''}>{item.indicador_1 ?? '—'}</TableCell>
                                            <TableCell className="whitespace-nowrap">{item.pilar_1 ?? '—'}</TableCell>
                                            <TableCell className="text-right">{fmtDecimal(item.total_1)}</TableCell>
                                            <TableCell className="text-right">{fmtDecimal(item.meta_1)}</TableCell>
                                            <TableCell className="max-w-[160px] truncate" title={item.indicador_2 ?? ''}>{item.indicador_2 ?? '—'}</TableCell>
                                            <TableCell className="whitespace-nowrap">{item.pilar_2 ?? '—'}</TableCell>
                                            <TableCell className="text-right">{fmtDecimal(item.total_2)}</TableCell>
                                            <TableCell className="text-right">{fmtDecimal(item.meta_2)}</TableCell>
                                            <TableCell className="max-w-[160px] truncate" title={item.indicador_3 ?? ''}>{item.indicador_3 ?? '—'}</TableCell>
                                            <TableCell className="whitespace-nowrap">{item.pilar_3 ?? '—'}</TableCell>
                                            <TableCell className="text-right">{fmtDecimal(item.total_3)}</TableCell>
                                            <TableCell className="text-right">{fmtDecimal(item.meta_3)}</TableCell>
                                            <TableCell className="whitespace-nowrap">{item.podium ?? '—'}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </>
                )}

                {/* Paginación */}
                {incentivos.links.length > 3 && (
                    <div className="flex flex-wrap gap-1">
                        {incentivos.links.map((link, index) => (
                            <Button
                                key={index}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                asChild={!!link.url}
                            >
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
