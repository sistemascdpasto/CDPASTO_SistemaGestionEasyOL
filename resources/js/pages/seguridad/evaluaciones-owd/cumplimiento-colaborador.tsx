import HeadingSmall from '@/components/heading-small';
import { KpiCard, KpiCardGrid } from '@/components/kpi-card';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

interface Colaborador {
    id: number;
    nombres: string;
    apellidos: string;
    cedula: string;
    codigo_qr_skap: string | null;
}

interface PreguntaIncumplida {
    id: number;
    proceso: string | null;
    tarea: string | null;
    puntuacion: string | null;
    evaluacion_owd: { id: number; fecha_evaluacion: string | null; pillar: string | null };
}

interface Cumplimiento {
    periodo_desde: string;
    periodo_hasta: string;
    total_preguntas: number;
    preguntas_ok: number;
    preguntas_no_conformes: number;
    porcentaje: number;
    cumple: boolean;
    faltantes: number;
    preguntas_incumplidas: PreguntaIncumplida[];
}

interface Evolucion {
    periodo: string;
    porcentaje: number;
    cumple: boolean;
    total_preguntas: number;
}

export default function CumplimientoColaborador({
    colaborador,
    cumplimiento,
    evolucion,
}: {
    colaborador: Colaborador;
    cumplimiento: Cumplimiento;
    evolucion?: Evolucion[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Seguridad', href: '/modules/seguridad' },
        { title: 'Evaluaciones OWD', href: '/modules/seguridad/evaluaciones-owd' },
        { title: 'Indicadores', href: '/modules/seguridad/evaluaciones-owd-indicadores' },
        { title: `${colaborador.nombres} ${colaborador.apellidos}`, href: `/modules/seguridad/evaluaciones-owd-cumplimiento/${colaborador.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Cumplimiento OWD — ${colaborador.nombres} ${colaborador.apellidos}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <HeadingSmall
                        title={`Cumplimiento OWD — ${colaborador.nombres} ${colaborador.apellidos}`}
                        description={`Período evaluado: ${cumplimiento.periodo_desde} a ${cumplimiento.periodo_hasta} (últimos 3 meses)`}
                    />
                    <Badge variant={cumplimiento.cumple ? 'default' : 'destructive'} className="text-sm">
                        {cumplimiento.cumple ? 'CUMPLE' : 'NO CUMPLE'} — {cumplimiento.porcentaje}%
                    </Badge>
                </div>

                <KpiCardGrid className="sm:grid-cols-2 lg:grid-cols-4">
                    <KpiCard label="Preguntas evaluadas" value={cumplimiento.total_preguntas} />
                    <KpiCard label="Preguntas OK" value={cumplimiento.preguntas_ok} />
                    <KpiCard label="No conformes" value={cumplimiento.preguntas_no_conformes} />
                    <KpiCard label="Faltan para el 100%" value={cumplimiento.faltantes} />
                </KpiCardGrid>

                {evolucion && evolucion.length > 0 && (
                    <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader>
                            <CardTitle className="text-sm font-medium text-muted-foreground">Evolución del cumplimiento</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={240}>
                                <AreaChart data={evolucion}>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                    <XAxis dataKey="periodo" tick={{ fontSize: 12 }} />
                                    <YAxis domain={[0, 100]} tick={{ fontSize: 12 }} unit="%" />
                                    <Tooltip />
                                    <Area type="monotone" dataKey="porcentaje" stroke="#3F7A22" fill="#3F7A22" fillOpacity={0.2} />
                                </AreaChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                )}

                <div>
                    <p className="mb-2 text-sm font-medium text-foreground">Preguntas que generaron incumplimiento</p>
                    <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Pillar</TableHead>
                                    <TableHead>Proceso</TableHead>
                                    <TableHead>Tarea</TableHead>
                                    <TableHead>Puntuación</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {cumplimiento.preguntas_incumplidas.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-6 text-center text-muted-foreground">
                                            Sin incumplimientos en el período.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {cumplimiento.preguntas_incumplidas.map((pregunta) => (
                                    <TableRow key={pregunta.id}>
                                        <TableCell>
                                            <Link href={route('seguridad.evaluaciones-owd.show', pregunta.evaluacion_owd.id)} className="text-primary hover:underline">
                                                {pregunta.evaluacion_owd.fecha_evaluacion
                                                    ? new Date(pregunta.evaluacion_owd.fecha_evaluacion).toLocaleDateString()
                                                    : '—'}
                                            </Link>
                                        </TableCell>
                                        <TableCell>{pregunta.evaluacion_owd.pillar ?? '—'}</TableCell>
                                        <TableCell>{pregunta.proceso ?? '—'}</TableCell>
                                        <TableCell className="max-w-xs truncate">{pregunta.tarea ?? '—'}</TableCell>
                                        <TableCell>
                                            <Badge variant="destructive">{pregunta.puntuacion}</Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
