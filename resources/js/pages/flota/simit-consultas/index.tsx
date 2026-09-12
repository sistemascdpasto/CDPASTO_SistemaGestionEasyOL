import HeadingSmall from '@/components/heading-small';
import { KpiCard, KpiCardGrid } from '@/components/kpi-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Gavel, Image, Search, Truck } from 'lucide-react';
import { FormEventHandler, useEffect, useRef, useState } from 'react';
import { Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Flota', href: '/modules/flota' },
    { title: 'Consultas SIMIT', href: '/modules/flota/simit-consultas' },
];

const TODOS = 'todos';

type ConsultaStatus = 'ok' | 'sin_comparendos' | 'captcha' | 'error';
type Vista = 'actual' | 'historico' | 'indicadores';

const STATUS_LABELS: Record<ConsultaStatus, string> = {
    ok: 'Con comparendos',
    sin_comparendos: 'Sin comparendos',
    captcha: 'Captcha (revisar)',
    error: 'Error de consulta',
};

const STATUS_VARIANTS: Record<ConsultaStatus, 'default' | 'secondary' | 'destructive'> = {
    ok: 'destructive',
    sin_comparendos: 'default',
    captcha: 'secondary',
    error: 'destructive',
};

// Paleta de estado validada (good/warning/serious/critical) — pasa el
// chequeo de daltonismo al combinarse en un mismo gráfico, a diferencia
// de los colores sueltos que usan otras páginas de indicadores de la app.
const STATUS_CHART_COLORS: Record<ConsultaStatus, string> = {
    sin_comparendos: '#0ca30c', // good
    captcha: '#fab219', // warning
    error: '#ec835a', // serious
    ok: '#d03b3b', // critical
};

interface ConsultaRow {
    id: number;
    placa: string;
    fecha_hora: string;
    status: ConsultaStatus;
    raw_text: string | null;
    screenshot_nombre: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface ConsultasPaginator {
    data: ConsultaRow[];
    links: PaginationLink[];
    from: number | null;
}

type Filters = {
    search: string;
    status: string;
    fecha_desde: string;
    fecha_hasta: string;
};

interface TendenciaDia {
    fecha: string;
    sin_comparendos: number;
    ok: number;
    captcha: number;
    error: number;
}

interface TopPlaca {
    placa: string;
    total: number;
}

interface Indicadores {
    resumen: {
        total_placas: number;
        con_comparendos: number;
        sin_comparendos: number;
        requieren_atencion: number;
    };
    tendencia_diaria: TendenciaDia[];
    top_placas_comparendos: TopPlaca[];
}

function formatFechaCorta(fecha: string): string {
    const [, mes, dia] = fecha.split('-');
    return `${dia}/${mes}`;
}

function PantallazoButton({ consulta, onVer }: { consulta: ConsultaRow; onVer: (consulta: ConsultaRow) => void }) {
    if (!consulta.screenshot_nombre) {
        return <span className="text-muted-foreground">—</span>;
    }

    return (
        <Button type="button" variant="ghost" size="icon" aria-label="Ver pantallazo" onClick={() => onVer(consulta)}>
            <Image className="size-4" />
        </Button>
    );
}

export default function SimitConsultasIndex({
    consultas,
    actuales,
    indicadores,
    filters,
}: {
    consultas: ConsultasPaginator;
    actuales: ConsultaRow[];
    indicadores: Indicadores;
    filters: Filters;
}) {
    const [vista, setVista] = useState<Vista>('actual');
    const [search, setSearch] = useState(filters.search);
    const [preview, setPreview] = useState<{ consulta: ConsultaRow; url: string } | null>(null);
    const isFirstRender = useRef(true);

    const verPantallazo = (consulta: ConsultaRow) =>
        setPreview({ consulta, url: route('flota.simit-consultas.screenshot', consulta.id) });

    const applyFilters = (overrides: Partial<Filters>) => {
        router.get(route('flota.simit-consultas.index'), { ...filters, search, ...overrides }, { preserveState: true, replace: true });
    };

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        const timeout = setTimeout(() => applyFilters({ search }), 400);
        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const submitFilters: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters({ search });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Consultas SIMIT" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <HeadingSmall
                        title="Consultas SIMIT"
                        description="Comparendos por placa, capturados automáticamente cada día."
                    />
                    <div className="flex rounded-md border border-sidebar-border/70 p-1">
                        <Button type="button" variant={vista === 'actual' ? 'default' : 'ghost'} size="sm" onClick={() => setVista('actual')}>
                            Estado actual
                        </Button>
                        <Button type="button" variant={vista === 'historico' ? 'default' : 'ghost'} size="sm" onClick={() => setVista('historico')}>
                            Histórico
                        </Button>
                        <Button
                            type="button"
                            variant={vista === 'indicadores' ? 'default' : 'ghost'}
                            size="sm"
                            onClick={() => setVista('indicadores')}
                        >
                            Indicadores
                        </Button>
                    </div>
                </div>

                {vista === 'indicadores' ? (
                    <div className="flex flex-col gap-6">
                        <KpiCardGrid className="sm:grid-cols-2 lg:grid-cols-4">
                            <KpiCard label="Placas monitoreadas" value={indicadores.resumen.total_placas} icon={Truck} color="#0ca30c" />
                            <KpiCard
                                label="Con comparendos pendientes"
                                value={indicadores.resumen.con_comparendos}
                                icon={Gavel}
                                color="#d03b3b"
                            />
                            <KpiCard
                                label="Sin comparendos"
                                value={indicadores.resumen.sin_comparendos}
                                icon={CheckCircle2}
                                color="#0ca30c"
                            />
                            <KpiCard
                                label="Requieren revisión manual"
                                value={indicadores.resumen.requieren_atencion}
                                icon={AlertTriangle}
                                color="#fab219"
                            />
                        </KpiCardGrid>

                        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                            <CardHeader>
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Consultas por día (últimos 30 días)
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {indicadores.tendencia_diaria.every(
                                    (dia) => dia.sin_comparendos + dia.ok + dia.captcha + dia.error === 0,
                                ) ? (
                                    <p className="py-8 text-center text-sm text-muted-foreground">
                                        Todavía no hay consultas registradas en este rango.
                                    </p>
                                ) : (
                                    <ResponsiveContainer width="100%" height={280}>
                                        <BarChart data={indicadores.tendencia_diaria}>
                                            <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                            <XAxis
                                                dataKey="fecha"
                                                tickFormatter={formatFechaCorta}
                                                tick={{ fontSize: 12 }}
                                                interval={2}
                                            />
                                            <YAxis allowDecimals={false} tick={{ fontSize: 12 }} />
                                            <Tooltip labelFormatter={(label) => formatFechaCorta(String(label))} />
                                            <Legend
                                                formatter={(value: string) => STATUS_LABELS[value as ConsultaStatus] ?? value}
                                            />
                                            <Bar
                                                dataKey="sin_comparendos"
                                                stackId="dia"
                                                name="sin_comparendos"
                                                fill={STATUS_CHART_COLORS.sin_comparendos}
                                            />
                                            <Bar dataKey="ok" stackId="dia" name="ok" fill={STATUS_CHART_COLORS.ok} />
                                            <Bar dataKey="captcha" stackId="dia" name="captcha" fill={STATUS_CHART_COLORS.captcha} />
                                            <Bar
                                                dataKey="error"
                                                stackId="dia"
                                                name="error"
                                                radius={[4, 4, 0, 0]}
                                                fill={STATUS_CHART_COLORS.error}
                                            />
                                        </BarChart>
                                    </ResponsiveContainer>
                                )}
                            </CardContent>
                        </Card>

                        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                            <CardHeader>
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Placas con más comparendos detectados (histórico)
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {indicadores.top_placas_comparendos.length === 0 ? (
                                    <p className="py-8 text-center text-sm text-muted-foreground">
                                        Ninguna placa ha registrado comparendos todavía.
                                    </p>
                                ) : (
                                    <ResponsiveContainer width="100%" height={280}>
                                        <BarChart data={indicadores.top_placas_comparendos}>
                                            <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                            <XAxis dataKey="placa" tick={{ fontSize: 12 }} interval={0} angle={-20} textAnchor="end" height={50} />
                                            <YAxis allowDecimals={false} tick={{ fontSize: 12 }} />
                                            <Tooltip />
                                            <Bar dataKey="total" name="Consultas con comparendos" fill={STATUS_CHART_COLORS.ok} radius={[4, 4, 0, 0]} />
                                        </BarChart>
                                    </ResponsiveContainer>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                ) : vista === 'actual' ? (
                    <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Placa</TableHead>
                                    <TableHead>Última consulta</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-right">Pantallazo</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {actuales.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-muted-foreground py-6 text-center">
                                            Todavía no hay consultas registradas.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {actuales.map((consulta) => (
                                    <TableRow key={consulta.placa}>
                                        <TableCell className="font-medium">{consulta.placa}</TableCell>
                                        <TableCell>{new Date(consulta.fecha_hora).toLocaleString()}</TableCell>
                                        <TableCell>
                                            <Badge variant={STATUS_VARIANTS[consulta.status]}>{STATUS_LABELS[consulta.status]}</Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <PantallazoButton consulta={consulta} onVer={verPantallazo} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                ) : (
                    <>
                        <div className="flex flex-wrap items-end gap-2">
                            <form onSubmit={submitFilters} className="flex max-w-xs items-center gap-2">
                                <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Buscar por placa..." />
                                <Button type="submit" variant="secondary" size="icon" aria-label="Buscar">
                                    <Search className="size-4" />
                                </Button>
                            </form>

                            <Select value={filters.status || TODOS} onValueChange={(value) => applyFilters({ status: value === TODOS ? '' : value })}>
                                <SelectTrigger className="w-52">
                                    <SelectValue placeholder="Estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={TODOS}>Estado: todos</SelectItem>
                                    {(Object.keys(STATUS_LABELS) as ConsultaStatus[]).map((status) => (
                                        <SelectItem key={status} value={status}>
                                            {STATUS_LABELS[status]}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <div className="grid gap-1">
                                <Label className="text-xs text-muted-foreground">Desde</Label>
                                <Input
                                    type="date"
                                    className="w-40"
                                    value={filters.fecha_desde}
                                    onChange={(e) => applyFilters({ fecha_desde: e.target.value })}
                                />
                            </div>
                            <div className="grid gap-1">
                                <Label className="text-xs text-muted-foreground">Hasta</Label>
                                <Input
                                    type="date"
                                    className="w-40"
                                    value={filters.fecha_hasta}
                                    onChange={(e) => applyFilters({ fecha_hasta: e.target.value })}
                                />
                            </div>
                        </div>

                        <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>#</TableHead>
                                        <TableHead>Placa</TableHead>
                                        <TableHead>Fecha y hora</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead className="text-right">Pantallazo</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {consultas.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-muted-foreground py-6 text-center">
                                                No se encontraron consultas.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {consultas.data.map((consulta, index) => (
                                        <TableRow key={consulta.id}>
                                            <TableCell className="text-muted-foreground">{(consultas.from ?? 1) + index}</TableCell>
                                            <TableCell className="font-medium">{consulta.placa}</TableCell>
                                            <TableCell>{new Date(consulta.fecha_hora).toLocaleString()}</TableCell>
                                            <TableCell>
                                                <Badge variant={STATUS_VARIANTS[consulta.status]}>{STATUS_LABELS[consulta.status]}</Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <PantallazoButton consulta={consulta} onVer={verPantallazo} />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {consultas.links.length > 3 && (
                            <div className="flex flex-wrap gap-1">
                                {consultas.links.map((link, index) => (
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
                    </>
                )}
            </div>

            <Dialog open={preview !== null} onOpenChange={(open) => !open && setPreview(null)}>
                <DialogContent className="max-h-[90vh] max-w-3xl overflow-y-auto">
                    <DialogTitle>
                        {preview?.consulta.placa} — {preview && new Date(preview.consulta.fecha_hora).toLocaleString()}
                    </DialogTitle>
                    {preview && (
                        <div className="space-y-4">
                            <img src={preview.url} alt={`Pantallazo SIMIT ${preview.consulta.placa}`} className="w-full rounded-md border border-border" />
                            {preview.consulta.raw_text && (
                                <p className="text-sm whitespace-pre-line text-muted-foreground">{preview.consulta.raw_text}</p>
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
