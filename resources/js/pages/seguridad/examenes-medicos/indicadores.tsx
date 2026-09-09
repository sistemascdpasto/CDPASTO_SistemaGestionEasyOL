import HeadingSmall from '@/components/heading-small';
import { KpiCard, KpiCardGrid } from '@/components/kpi-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { CheckCircle2, ClipboardList, Stethoscope } from 'lucide-react';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Seguridad', href: '/modules/seguridad' },
    { title: 'Exámenes Médicos', href: '/modules/seguridad/examenes-medicos' },
    { title: 'Indicadores', href: '/modules/seguridad/examenes-medicos-indicadores' },
];

interface Distribucion {
    valor: string;
    etiqueta: string;
    cantidad: number;
}

interface ConteoNombre {
    nombre: string;
    cantidad: number;
}

interface Cobertura {
    programados: number;
    ejecutados: number;
    porcentaje: number;
}

function DistribucionChart({ titulo, datos, color }: { titulo: string; datos: Distribucion[]; color: string }) {
    return (
        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
            <CardHeader>
                <CardTitle className="text-sm font-medium text-muted-foreground">{titulo}</CardTitle>
            </CardHeader>
            <CardContent>
                <ResponsiveContainer width="100%" height={240}>
                    <BarChart data={datos}>
                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                        <XAxis dataKey="etiqueta" tick={{ fontSize: 12 }} interval={0} angle={-15} textAnchor="end" height={50} />
                        <YAxis allowDecimals={false} tick={{ fontSize: 12 }} />
                        <Tooltip />
                        <Bar dataKey="cantidad" fill={color} radius={[4, 4, 0, 0]} />
                    </BarChart>
                </ResponsiveContainer>
            </CardContent>
        </Card>
    );
}

function ConteoTable({
    titulo,
    datos,
    columna = 'Recomendación',
    vacio = 'Sin recomendaciones registradas todavía.',
}: {
    titulo: string;
    datos: ConteoNombre[];
    columna?: string;
    vacio?: string;
}) {
    return (
        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
            <CardHeader>
                <CardTitle className="text-sm font-medium text-muted-foreground">{titulo}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="max-h-72 overflow-y-auto rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{columna}</TableHead>
                                <TableHead className="text-right">Colaboradores</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {datos.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={2} className="text-muted-foreground py-6 text-center">
                                        {vacio}
                                    </TableCell>
                                </TableRow>
                            )}
                            {datos.map((fila) => (
                                <TableRow key={fila.nombre}>
                                    <TableCell className="text-sm">{fila.nombre}</TableCell>
                                    <TableCell className="text-right font-medium">{fila.cantidad}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    );
}

export default function ExamenesMedicosIndicadores({
    cobertura,
    porTipoEvaluacion,
    porConceptoAptitud,
    recomendacionesMedicas,
    recomendacionesOcupacionales,
    habitosEstilosVida,
    estadoExamen,
    programacionExamenes,
}: {
    cobertura: Cobertura;
    porTipoEvaluacion: Distribucion[];
    porConceptoAptitud: ConteoNombre[];
    recomendacionesMedicas: ConteoNombre[];
    recomendacionesOcupacionales: ConteoNombre[];
    habitosEstilosVida: ConteoNombre[];
    estadoExamen: Distribucion[];
    programacionExamenes: Distribucion[];
}) {
    const kpis = [
        { label: 'Cobertura de ejecución', valor: cobertura.porcentaje, suffix: '%', decimals: 1, icon: CheckCircle2, color: '#3F7A22' },
        { label: 'Exámenes ejecutados', valor: cobertura.ejecutados, icon: ClipboardList, color: '#0369A1' },
        { label: 'Exámenes programados', valor: cobertura.programados, icon: Stethoscope, color: '#B45309' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Indicadores — Exámenes Médicos" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <HeadingSmall
                    title="Indicadores — Exámenes Médicos"
                    description="Panel general del programa de evaluaciones médicas ocupacionales."
                />

                <KpiCardGrid className="sm:grid-cols-3">
                    {kpis.map((kpi) => (
                        <KpiCard
                            key={kpi.label}
                            label={kpi.label}
                            value={kpi.valor}
                            suffix={kpi.suffix}
                            decimals={kpi.decimals}
                            icon={kpi.icon}
                            color={kpi.color}
                        />
                    ))}
                </KpiCardGrid>

                <div className="grid gap-4 lg:grid-cols-3">
                    <DistribucionChart titulo="Por tipo de evaluación" datos={porTipoEvaluacion} color="#3F7A22" />
                    <DistribucionChart titulo="Estado de las evaluaciones" datos={estadoExamen} color="#0369A1" />
                    <DistribucionChart titulo="Programación de exámenes" datos={programacionExamenes} color="#B45309" />
                </div>

                <ConteoTable
                    titulo="Concepto aptitudinal"
                    datos={porConceptoAptitud}
                    columna="Concepto"
                    vacio="Sin conceptos de aptitud registrados todavía."
                />

                <div className="grid gap-4 lg:grid-cols-3">
                    <ConteoTable titulo="Recomendaciones médicas" datos={recomendacionesMedicas} />
                    <ConteoTable titulo="Recomendaciones ocupacionales" datos={recomendacionesOcupacionales} />
                    <ConteoTable titulo="Hábitos y estilos de vida saludables" datos={habitosEstilosVida} />
                </div>
            </div>
        </AppLayout>
    );
}
