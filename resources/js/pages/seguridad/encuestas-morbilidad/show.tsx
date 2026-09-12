import HeadingSmall from '@/components/heading-small';
import { SeccionLectura } from '@/components/morbilidad/seccion-lectura';
import { seccionesOrdenadas, type RespuestasState, type SeccionesCatalogo } from '@/components/morbilidad/types';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';

interface ColaboradorDetalle {
    id: number;
    nombres: string;
    apellidos: string;
    cedula: string;
    area: string | null;
    cargo: string | null;
}

interface EncuestaDetalle {
    id: number;
    fecha_hora: string;
    enviado_en: string | null;
}

interface Hijo { nombre: string; edad: string }
interface PersonaCargo { tipo: string; edad: string }

interface Paso1Data {
    empresa: string | null;
    correo_electronico: string | null;
    edad: number | null;
    estado_civil: string | null;
    tiene_hijos: string | null;
    hijos: Hijo[] | null;
    personas_a_cargo: string | null;
    personas_cargo_detalle: PersonaCargo[] | null;
    nivel_escolaridad: string | null;
    estrato_socioeconomico: string | null;
    tenencia_vivienda: string | null;
    ciudad_residencia: string | null;
    direccion_residencia: string | null;
    tipo_contratacion: string | null;
    cargo_paso1: string | null;
    area_paso1: string | null;
    antiguedad_empresa: string | null;
    antiguedad_cargo: string | null;
    duracion_contrato: string | null;
    turno: string | null;
    promedio_ingresos: string | null;
}

function SeccionPaso1Lectura({ paso1 }: { paso1?: Paso1Data | null }) {
    if (!paso1) return null;

    return (
        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
            <CardContent className="pt-6 space-y-6">
                <p className="text-sm font-medium text-foreground">
                    1. Datos sociodemográficos y laborales
                </p>

                {/* A. Identificación */}
                <div className="space-y-2">
                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        A. Identificación
                    </p>
                    <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-4 rounded-lg border border-border p-3 text-xs">
                        <div><span className="text-muted-foreground block">Empresa</span> <span className="font-medium text-foreground">{paso1.empresa || '—'}</span></div>
                        <div><span className="text-muted-foreground block">Correo electrónico</span> <span className="font-medium text-foreground">{paso1.correo_electronico || '—'}</span></div>
                        <div><span className="text-muted-foreground block">Edad</span> <span className="font-medium text-foreground">{paso1.edad ?? '—'}</span></div>
                        <div><span className="text-muted-foreground block">Estado civil</span> <span className="font-medium text-foreground">{paso1.estado_civil || '—'}</span></div>
                    </div>
                </div>

                {/* B. Familia */}
                <div className="space-y-2">
                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        B. Familia
                    </p>
                    <div className="grid gap-3 sm:grid-cols-2 rounded-lg border border-border p-3 text-xs">
                        <div>
                            <span className="text-muted-foreground block">¿Tiene hijos(as)?</span>
                            <span className="font-medium text-foreground">{paso1.tiene_hijos || '—'}</span>
                            {paso1.tiene_hijos === 'Si' && paso1.hijos && paso1.hijos.length > 0 && (
                                <ul className="mt-1.5 space-y-0.5 pl-3 list-disc text-muted-foreground">
                                    {paso1.hijos.map((h, idx) => (
                                        <li key={idx}><span className="text-foreground font-medium">{h.nombre}</span> ({h.edad} años)</li>
                                    ))}
                                </ul>
                            )}
                        </div>
                        <div>
                            <span className="text-muted-foreground block">¿Tiene personas a cargo?</span>
                            <span className="font-medium text-foreground">{paso1.personas_a_cargo || '—'}</span>
                            {paso1.personas_a_cargo === 'Si' && paso1.personas_cargo_detalle && paso1.personas_cargo_detalle.length > 0 && (
                                <ul className="mt-1.5 space-y-0.5 pl-3 list-disc text-muted-foreground">
                                    {paso1.personas_cargo_detalle.map((p, idx) => (
                                        <li key={idx}><span className="text-foreground font-medium">{p.tipo}</span> ({p.edad} años)</li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </div>
                </div>

                {/* C. Educación y vivienda */}
                <div className="space-y-2">
                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        C. Educación y vivienda
                    </p>
                    <div className="grid gap-3 sm:grid-cols-3 rounded-lg border border-border p-3 text-xs">
                        <div><span className="text-muted-foreground block">Nivel de escolaridad</span> <span className="font-medium text-foreground">{paso1.nivel_escolaridad || '—'}</span></div>
                        <div><span className="text-muted-foreground block">Estrato socioeconómico</span> <span className="font-medium text-foreground">{paso1.estrato_socioeconomico || '—'}</span></div>
                        <div><span className="text-muted-foreground block">Tenencia de vivienda</span> <span className="font-medium text-foreground">{paso1.tenencia_vivienda || '—'}</span></div>
                    </div>
                </div>

                {/* D. Residencia */}
                <div className="space-y-2">
                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        D. Residencia
                    </p>
                    <div className="grid gap-3 sm:grid-cols-2 rounded-lg border border-border p-3 text-xs">
                        <div><span className="text-muted-foreground block">Ciudad / Municipio</span> <span className="font-medium text-foreground">{paso1.ciudad_residencia || '—'}</span></div>
                        <div><span className="text-muted-foreground block">Dirección</span> <span className="font-medium text-foreground">{paso1.direccion_residencia || '—'}</span></div>
                    </div>
                </div>

                {/* E. Información laboral */}
                <div className="space-y-2">
                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        E. Información laboral
                    </p>
                    <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-3 rounded-lg border border-border p-3 text-xs">
                        <div><span className="text-muted-foreground block">Tipo de contratación</span> <span className="font-medium text-foreground">{paso1.tipo_contratacion || '—'}</span></div>
                        <div><span className="text-muted-foreground block">Cargo</span> <span className="font-medium text-foreground">{paso1.cargo_paso1 || '—'}</span></div>
                        <div><span className="text-muted-foreground block">Área</span> <span className="font-medium text-foreground">{paso1.area_paso1 || '—'}</span></div>
                        <div><span className="text-muted-foreground block">Antigüedad empresa</span> <span className="font-medium text-foreground">{paso1.antiguedad_empresa || '—'}</span></div>
                        <div><span className="text-muted-foreground block">Antigüedad cargo</span> <span className="font-medium text-foreground">{paso1.antiguedad_cargo || '—'}</span></div>
                        <div><span className="text-muted-foreground block">Duración contrato</span> <span className="font-medium text-foreground">{paso1.duracion_contrato || '—'}</span></div>
                        <div><span className="text-muted-foreground block">Turno</span> <span className="font-medium text-foreground">{paso1.turno || '—'}</span></div>
                        <div className="sm:col-span-2"><span className="text-muted-foreground block">Promedio de ingresos</span> <span className="font-medium text-foreground">{paso1.promedio_ingresos || '—'}</span></div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function EncuestaMorbilidadShowSST({
    encuesta,
    colaborador,
    paso1,
    secciones,
    respuestas,
}: {
    encuesta: EncuestaDetalle;
    colaborador: ColaboradorDetalle;
    paso1?: Paso1Data | null;
    secciones: SeccionesCatalogo;
    respuestas: RespuestasState;
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Seguridad', href: '/modules/seguridad' },
        { title: 'Encuestas de Morbilidad', href: '/modules/seguridad/encuestas-morbilidad' },
        { title: `${colaborador.nombres} ${colaborador.apellidos}`, href: `/modules/seguridad/encuestas-morbilidad/${encuesta.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Encuesta de Morbilidad — ${colaborador.nombres} ${colaborador.apellidos}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <HeadingSmall
                    title={`${colaborador.nombres} ${colaborador.apellidos} — ${colaborador.cedula}`}
                    description={`${[colaborador.area, colaborador.cargo].filter(Boolean).join(' · ')} · Enviada el ${
                        encuesta.enviado_en ? new Date(encuesta.enviado_en).toLocaleString() : '—'
                    }`}
                />

                <SeccionPaso1Lectura paso1={paso1} />

                {seccionesOrdenadas(secciones).map((seccion) => (
                    <Card key={seccion.numero} className="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardContent className="pt-6">
                            <div className="mb-4 flex items-center gap-2">
                                <p className="text-sm font-medium text-foreground">
                                    {seccion.numero + 1}. {seccion.titulo}
                                </p>
                                {seccion.sensible && (
                                    <Badge variant="destructive" className="gap-1">
                                        <ShieldAlert className="size-3" />
                                        Información sensible
                                    </Badge>
                                )}
                            </div>
                            <SeccionLectura seccion={seccion} respuestas={respuestas} />
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AppLayout>
    );
}
