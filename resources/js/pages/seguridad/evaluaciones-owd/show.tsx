import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { SeccionCard } from '@/pages/seguridad/colaboradores/colaborador-form-fields';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ClipboardList, ListChecks, MapPin, Users } from 'lucide-react';

interface ColaboradorLigero {
    id: number;
    nombres: string;
    apellidos: string;
    cedula: string;
}

interface PlanAccion {
    id: number;
    estado: string;
    fecha_vencimiento: string | null;
}

interface Pregunta {
    id: number;
    proceso: string | null;
    actividad: string | null;
    tarea: string | null;
    descripcion: string | null;
    puntuacion: string | null;
    ponderacion: string | null;
    requiere_plan_accion: boolean;
    version: string | null;
    datos_adicionales: Record<string, string> | null;
    plan_accion: PlanAccion | null;
}

interface EvaluacionOwdDetalle {
    id: number;
    bu: string | null;
    pais: string | null;
    region: string | null;
    uen: string | null;
    id_agencia: string | null;
    agencia: string | null;
    evaluador: string | null;
    posicion_evaluador: string | null;
    qr_safety_evaluador: string | null;
    evaluador_colaborador: ColaboradorLigero | null;
    evaluado: string | null;
    posicion: string | null;
    qr_safety: string | null;
    colaborador: ColaboradorLigero | null;
    fecha_evaluacion: string | null;
    type: string | null;
    pillar: string | null;
    total_preguntas: number;
    preguntas_ok: number;
    preguntas_no_ok: number;
    preguntas_na: number;
    preguntas: Pregunta[];
}

function Campo({ label, valor }: { label: string; valor: React.ReactNode }) {
    return (
        <div>
            <p className="text-xs font-medium text-muted-foreground">{label}</p>
            <p className="text-sm text-foreground">{valor ?? '—'}</p>
        </div>
    );
}

function badgePuntuacion(puntuacion: string | null) {
    if (puntuacion === 'OK') return <Badge>OK</Badge>;
    if (puntuacion === 'Not Applicable') return <Badge variant="secondary">N/A</Badge>;
    if (!puntuacion) return <span className="text-muted-foreground">—</span>;
    return <Badge variant="destructive">{puntuacion}</Badge>;
}

export default function EvaluacionOwdShow({ evaluacionOwd }: { evaluacionOwd: EvaluacionOwdDetalle }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Seguridad', href: '/modules/seguridad' },
        { title: 'Evaluaciones OWD', href: '/modules/seguridad/evaluaciones-owd' },
        { title: `Evaluación #${evaluacionOwd.id}`, href: `/modules/seguridad/evaluaciones-owd/${evaluacionOwd.id}` },
    ];

    const nombreEvaluado = evaluacionOwd.colaborador
        ? `${evaluacionOwd.colaborador.nombres} ${evaluacionOwd.colaborador.apellidos}`
        : evaluacionOwd.evaluado;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Evaluación OWD — ${nombreEvaluado ?? evaluacionOwd.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <HeadingSmall
                        title={`Evaluación OWD — ${nombreEvaluado ?? '—'}`}
                        description={evaluacionOwd.fecha_evaluacion ? new Date(evaluacionOwd.fecha_evaluacion).toLocaleString() : '—'}
                    />
                    <div className="flex gap-2">
                        <Badge>{evaluacionOwd.preguntas_ok} OK</Badge>
                        {evaluacionOwd.preguntas_no_ok > 0 && <Badge variant="destructive">{evaluacionOwd.preguntas_no_ok} No OK</Badge>}
                        {evaluacionOwd.preguntas_na > 0 && <Badge variant="secondary">{evaluacionOwd.preguntas_na} N/A</Badge>}
                    </div>
                </div>

                <SeccionCard icon={Users} titulo="Evaluador y evaluado" tono="azul">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p className="text-xs font-medium text-muted-foreground">Evaluador</p>
                            {evaluacionOwd.evaluador_colaborador ? (
                                <Link
                                    href={route('gente.colaboradores.show', evaluacionOwd.evaluador_colaborador.id)}
                                    className="text-sm text-primary hover:underline"
                                >
                                    {evaluacionOwd.evaluador_colaborador.nombres} {evaluacionOwd.evaluador_colaborador.apellidos}
                                </Link>
                            ) : (
                                <p className="text-sm text-foreground">{evaluacionOwd.evaluador ?? '—'}</p>
                            )}
                            {evaluacionOwd.qr_safety_evaluador && (
                                <p className="text-xs text-muted-foreground">QR Safety: {evaluacionOwd.qr_safety_evaluador}</p>
                            )}
                        </div>
                        <Campo label="Posición evaluador" valor={evaluacionOwd.posicion_evaluador} />
                        <div>
                            <p className="text-xs font-medium text-muted-foreground">Evaluado</p>
                            {evaluacionOwd.colaborador ? (
                                <Link
                                    href={route('gente.colaboradores.show', evaluacionOwd.colaborador.id)}
                                    className="text-sm text-primary hover:underline"
                                >
                                    {evaluacionOwd.colaborador.nombres} {evaluacionOwd.colaborador.apellidos} — {evaluacionOwd.colaborador.cedula}
                                </Link>
                            ) : (
                                <p className="text-sm text-foreground">{evaluacionOwd.evaluado ?? '—'}</p>
                            )}
                            {evaluacionOwd.qr_safety && <p className="text-xs text-muted-foreground">QR Safety: {evaluacionOwd.qr_safety}</p>}
                        </div>
                        <Campo label="Posición evaluado" valor={evaluacionOwd.posicion} />
                    </div>
                </SeccionCard>

                <SeccionCard icon={MapPin} titulo="Ubicación y clasificación" tono="verde">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Campo label="BU" valor={evaluacionOwd.bu} />
                        <Campo label="País" valor={evaluacionOwd.pais} />
                        <Campo label="Región" valor={evaluacionOwd.region} />
                        <Campo label="UEN" valor={evaluacionOwd.uen} />
                        <Campo label="Agencia" valor={evaluacionOwd.agencia} />
                        <Campo label="ID Agencia" valor={evaluacionOwd.id_agencia} />
                        <Campo label="Type" valor={evaluacionOwd.type} />
                        <Campo label="Pillar" valor={evaluacionOwd.pillar} />
                    </div>
                </SeccionCard>

                <SeccionCard icon={ClipboardList} titulo="Preguntas evaluadas" subtitulo={`${evaluacionOwd.total_preguntas} en total`} tono="neutral">
                    <div className="overflow-x-auto rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Proceso</TableHead>
                                    <TableHead>Actividad</TableHead>
                                    <TableHead>Tarea</TableHead>
                                    <TableHead>Puntuación</TableHead>
                                    <TableHead>Ponderación</TableHead>
                                    <TableHead>Plan de acción</TableHead>
                                    <TableHead>Versión</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {evaluacionOwd.preguntas.map((pregunta) => (
                                    <TableRow key={pregunta.id} className={pregunta.puntuacion === 'No OK' ? 'bg-destructive/5' : undefined}>
                                        <TableCell className="max-w-48">{pregunta.proceso ?? '—'}</TableCell>
                                        <TableCell className="max-w-48">{pregunta.actividad ?? '—'}</TableCell>
                                        <TableCell className="max-w-xs">
                                            <p className="text-sm">{pregunta.tarea ?? '—'}</p>
                                            {pregunta.descripcion && <p className="text-xs text-muted-foreground">{pregunta.descripcion}</p>}
                                        </TableCell>
                                        <TableCell>{badgePuntuacion(pregunta.puntuacion)}</TableCell>
                                        <TableCell>{pregunta.ponderacion ?? '—'}</TableCell>
                                        <TableCell>
                                            {pregunta.plan_accion ? (
                                                <Badge variant="destructive">{pregunta.plan_accion.estado}</Badge>
                                            ) : pregunta.requiere_plan_accion ? (
                                                <Badge variant="destructive">SI</Badge>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell>{pregunta.version ?? '—'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </SeccionCard>

                {evaluacionOwd.preguntas.some((p) => p.datos_adicionales && Object.keys(p.datos_adicionales).length > 0) && (
                    <SeccionCard icon={ListChecks} titulo="Datos adicionales" subtitulo="Columnas del Excel no reconocidas" tono="neutral">
                        <div className="space-y-4">
                            {evaluacionOwd.preguntas
                                .filter((p) => p.datos_adicionales && Object.keys(p.datos_adicionales).length > 0)
                                .map((p) => (
                                    <div key={p.id} className="grid gap-4 border-t border-border pt-3 first:border-t-0 first:pt-0 sm:grid-cols-2 lg:grid-cols-4">
                                        {Object.entries(p.datos_adicionales ?? {}).map(([clave, valor]) => (
                                            <Campo key={clave} label={clave} valor={valor} />
                                        ))}
                                    </div>
                                ))}
                        </div>
                    </SeccionCard>
                )}
            </div>
        </AppLayout>
    );
}
