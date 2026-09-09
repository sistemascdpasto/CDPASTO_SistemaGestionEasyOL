import HeadingSmall from '@/components/heading-small';
import { KpiCard, KpiCardGrid } from '@/components/kpi-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, ClipboardList, ListTodo, Target, Users } from 'lucide-react';
import { Area, AreaChart, Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Seguridad', href: '/modules/seguridad' },
    { title: 'Evaluaciones OWD', href: '/modules/seguridad/evaluaciones-owd' },
    { title: 'Indicadores', href: '/modules/seguridad/evaluaciones-owd-indicadores' },
];

const MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const MESES_ABREV = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

function formatearPeriodo(periodo: string): string {
    const [anio, mes] = periodo.split('-').map(Number);
    return `${MESES_ABREV[mes - 1]} ${anio}`;
}

interface Resumen {
    colaboradores_evaluados: number;
    cumplen_owd: number;
    no_cumplen_owd: number;
    porcentaje_cumplimiento: number;
    total_preguntas: number;
    total_preguntas_ok: number;
    total_preguntas_no_conformes: number;
    total_planes_accion: number;
    planes_vencidos: number;
}

interface UltimaEvaluacion {
    id: number;
    fecha_evaluacion: string | null;
    pillar: string | null;
    agencia: string | null;
    total_preguntas: number;
    preguntas_ok: number;
    preguntas_no_ok: number;
    colaborador: { nombres: string; apellidos: string } | null;
}

interface ColaboradorCumplimiento {
    id: number;
    nombres: string;
    apellidos: string;
    cedula: string;
    porcentaje: number;
    cumple: boolean;
    total_preguntas: number;
    preguntas_no_conformes: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface ColaboradoresCumplimientoPaginator {
    data: ColaboradorCumplimiento[];
    links: PaginationLink[];
    total: number;
}

interface GrupoCumplimiento {
    grupo: string;
    total: number;
    porcentaje: number;
}

export default function EvaluacionesOwdIndicadores({
    filtros,
    resumen,
    ultimasEvaluaciones,
    colaboradoresCumplimiento,
    cumplimientoPorPillar,
    cumplimientoPorAgencia,
    cumplimientoPorProceso,
    evaluacionesPorMes,
}: {
    filtros: { mes: number; anio: number };
    resumen: Resumen;
    ultimasEvaluaciones: UltimaEvaluacion[];
    colaboradoresCumplimiento: ColaboradoresCumplimientoPaginator;
    cumplimientoPorPillar: GrupoCumplimiento[];
    cumplimientoPorAgencia: GrupoCumplimiento[];
    cumplimientoPorProceso: GrupoCumplimiento[];
    evaluacionesPorMes: { periodo: string; cantidad: number }[];
}) {
    const cambiarFiltro = (cambios: Partial<{ mes: number; anio: number }>) => {
        router.get(route('seguridad.evaluaciones-owd.indicadores'), { ...filtros, ...cambios, page: 1 }, { preserveState: true, replace: true });
    };

    const kpis = [
        { label: 'Colaboradores evaluados', valor: resumen.colaboradores_evaluados, icon: Users, color: '#3F7A22' },
        {
            label: 'Cumplen OWD (100%)',
            valor: `${resumen.porcentaje_cumplimiento}% (${resumen.cumplen_owd}/${resumen.colaboradores_evaluados})`,
            icon: Target,
            color: '#0369A1',
        },
        { label: 'Preguntas no conformes', valor: resumen.total_preguntas_no_conformes, icon: AlertTriangle, color: '#B91C1C' },
        { label: 'Planes de acción', valor: resumen.total_planes_accion, icon: ListTodo, color: '#B45309' },
    ];

    const datosTendencia = evaluacionesPorMes.map((fila) => ({ ...fila, etiqueta: formatearPeriodo(fila.periodo) }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Indicadores OWD" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <HeadingSmall title="Indicadores OWD" description="Cumplimiento del programa de Observación de Trabajo Seguro (últimos 3 meses)." />
                    <div className="flex gap-2">
                        <Select value={String(filtros.mes)} onValueChange={(v) => cambiarFiltro({ mes: Number(v) })}>
                            <SelectTrigger className="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {MESES.map((mes, index) => (
                                    <SelectItem key={mes} value={String(index + 1)}>
                                        {mes}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={String(filtros.anio)} onValueChange={(v) => cambiarFiltro({ anio: Number(v) })}>
                            <SelectTrigger className="w-28">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {[filtros.anio - 1, filtros.anio, filtros.anio + 1].map((anio) => (
                                    <SelectItem key={anio} value={String(anio)}>
                                        {anio}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <KpiCardGrid className="sm:grid-cols-2 lg:grid-cols-4">
                    {kpis.map((kpi) => (
                        <KpiCard key={kpi.label} label={kpi.label} value={kpi.valor} icon={kpi.icon} color={kpi.color} />
                    ))}
                </KpiCardGrid>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader>
                            <CardTitle className="text-sm font-medium text-muted-foreground">Cumplimiento por Pillar</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {cumplimientoPorPillar.length === 0 ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">Sin datos para este período.</p>
                            ) : (
                                <ResponsiveContainer width="100%" height={260}>
                                    <BarChart data={cumplimientoPorPillar}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                        <XAxis dataKey="grupo" tick={{ fontSize: 12 }} interval={0} angle={-20} textAnchor="end" height={60} />
                                        <YAxis allowDecimals={false} tick={{ fontSize: 12 }} unit="%" />
                                        <Tooltip />
                                        <Bar dataKey="porcentaje" fill="#3F7A22" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader>
                            <CardTitle className="text-sm font-medium text-muted-foreground">Evaluaciones — últimos 6 meses</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={260}>
                                <AreaChart data={datosTendencia}>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                    <XAxis dataKey="etiqueta" tick={{ fontSize: 12 }} />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 12 }} />
                                    <Tooltip />
                                    <Area type="monotone" dataKey="cantidad" stroke="#0369A1" fill="#0369A1" fillOpacity={0.2} />
                                </AreaChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader>
                            <CardTitle className="text-sm font-medium text-muted-foreground">Cumplimiento por agencia</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {cumplimientoPorAgencia.length === 0 ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">Sin datos para este período.</p>
                            ) : (
                                <ResponsiveContainer width="100%" height={220}>
                                    <BarChart data={cumplimientoPorAgencia}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                        <XAxis dataKey="grupo" tick={{ fontSize: 12 }} interval={0} angle={-20} textAnchor="end" height={60} />
                                        <YAxis allowDecimals={false} tick={{ fontSize: 12 }} unit="%" />
                                        <Tooltip />
                                        <Bar dataKey="porcentaje" fill="#0369A1" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader>
                            <CardTitle className="text-sm font-medium text-muted-foreground">Cumplimiento por proceso</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {cumplimientoPorProceso.length === 0 ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">Sin datos para este período.</p>
                            ) : (
                                <ResponsiveContainer width="100%" height={220}>
                                    <BarChart data={cumplimientoPorProceso}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                        <XAxis dataKey="grupo" tick={{ fontSize: 12 }} interval={0} angle={-20} textAnchor="end" height={60} />
                                        <YAxis allowDecimals={false} tick={{ fontSize: 12 }} unit="%" />
                                        <Tooltip />
                                        <Bar dataKey="porcentaje" fill="#B45309" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div>
                    <p className="mb-2 text-sm font-medium text-foreground">Últimas evaluaciones</p>
                    <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Colaborador</TableHead>
                                    <TableHead>Pillar</TableHead>
                                    <TableHead>Agencia</TableHead>
                                    <TableHead>Resultado</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {ultimasEvaluaciones.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-6 text-center text-muted-foreground">
                                            Sin evaluaciones en este período.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {ultimasEvaluaciones.map((evaluacion) => (
                                    <TableRow key={evaluacion.id}>
                                        <TableCell className="font-medium">
                                            <Link href={route('seguridad.evaluaciones-owd.show', evaluacion.id)} className="text-primary hover:underline">
                                                {evaluacion.fecha_evaluacion ? new Date(evaluacion.fecha_evaluacion).toLocaleDateString() : '—'}
                                            </Link>
                                        </TableCell>
                                        <TableCell>
                                            {evaluacion.colaborador ? `${evaluacion.colaborador.nombres} ${evaluacion.colaborador.apellidos}` : '—'}
                                        </TableCell>
                                        <TableCell>{evaluacion.pillar ?? '—'}</TableCell>
                                        <TableCell>{evaluacion.agencia ?? '—'}</TableCell>
                                        <TableCell>
                                            <CheckCircle2 className="mr-1 inline size-3.5 text-emerald-600" />
                                            {evaluacion.preguntas_ok}/{evaluacion.total_preguntas} OK
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <div>
                    <p className="mb-2 text-sm font-medium text-foreground">
                        <ClipboardList className="mr-1 inline size-4" />
                        Cumplimiento OWD por colaborador
                        <span className="ml-1 text-xs font-normal text-muted-foreground">({colaboradoresCumplimiento.total} evaluados)</span>
                    </p>
                    <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Colaborador</TableHead>
                                    <TableHead>Cumplimiento</TableHead>
                                    <TableHead>No conformes</TableHead>
                                    <TableHead>Estado</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {colaboradoresCumplimiento.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={4} className="py-6 text-center text-muted-foreground">
                                            No hay colaboradores evaluados en este período.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {colaboradoresCumplimiento.data.map((c) => (
                                    <TableRow key={c.id}>
                                        <TableCell>
                                            <Link href={route('seguridad.evaluaciones-owd.cumplimiento.show', c.id)} className="text-primary hover:underline">
                                                {c.nombres} {c.apellidos} — {c.cedula}
                                            </Link>
                                        </TableCell>
                                        <TableCell>{c.porcentaje}%</TableCell>
                                        <TableCell>{c.preguntas_no_conformes}</TableCell>
                                        <TableCell>
                                            <Badge variant={c.cumple ? 'default' : 'destructive'}>{c.cumple ? 'Cumple' : 'No cumple'}</Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    {colaboradoresCumplimiento.links.length > 3 && (
                        <div className="mt-2 flex flex-wrap gap-1">
                            {colaboradoresCumplimiento.links.map((link, index) => (
                                <Button key={index} variant={link.active ? 'default' : 'outline'} size="sm" disabled={!link.url} asChild={!!link.url}>
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
            </div>
        </AppLayout>
    );
}
