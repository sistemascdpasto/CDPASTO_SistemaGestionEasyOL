import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { SeccionCard } from '@/pages/seguridad/colaboradores/colaborador-form-fields';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CalendarClock, ClipboardList, MapPin, ShieldAlert, Users } from 'lucide-react';

interface ColaboradorLigero {
    id: number;
    nombres: string;
    apellidos: string;
    cedula: string;
}

interface AciDetalle {
    id: number;
    folio: string;
    fecha_alzado: string | null;
    hora_alzado: string | null;
    fecha_incidente: string | null;
    hora_incidente: string | null;
    ubicacion: string | null;
    dentro_fuera_ubicacion: string | null;
    zona: string | null;
    subzona: string | null;
    reporte_de: string | null;
    persona_cometio: string | null;
    persona_cometio_pin: string | null;
    persona_cometio_numero_empleado: string | null;
    reportado_por: string | null;
    qr_reportante: string | null;
    colaborador: ColaboradorLigero | null;
    numero_empleado_reporto: string | null;
    pin_reporto: string | null;
    descripcion: string | null;
    posible_solucion: string | null;
    area: string | null;
    subarea: string | null;
    descripcion_ubicacion: string | null;
    clasificacion: string | null;
    tipo_riesgo: string | null;
    descripcion_tipo_riesgo: string | null;
    ciudad_elegida: string | null;
    estado_elegido: string | null;
    nombre_punto_venta: string | null;
    descripcion_punto_venta: string | null;
    tipo_acto_ruta: string | null;
    fue_por_externo: boolean | null;
    sif: boolean | null;
    estatus_asignacion: string | null;
    fecha_hora_asignacion: string | null;
    asignado_por: string | null;
    asignador: ColaboradorLigero | null;
    asignado_a: string | null;
    asignado: ColaboradorLigero | null;
    cerrado_por: string | null;
    fecha_hora_cierre: string | null;
    bu: string | null;
    compania: string | null;
    fecha_pospuesto: string | null;
    datos_adicionales: Record<string, string> | null;
}

function formatearFecha(valor: string | null): string {
    if (!valor) return '—';
    return new Date(valor).toLocaleDateString();
}

function formatearFechaHora(valor: string | null): string {
    if (!valor) return '—';
    return new Date(valor).toLocaleString();
}

function Campo({ label, valor }: { label: string; valor: React.ReactNode }) {
    return (
        <div>
            <p className="text-xs font-medium text-muted-foreground">{label}</p>
            <p className="text-sm text-foreground">{valor ?? '—'}</p>
        </div>
    );
}

function ColaboradorLink({ colaborador, textoAlterno }: { colaborador: ColaboradorLigero | null; textoAlterno: string | null }) {
    if (colaborador) {
        return (
            <Link href={route('gente.colaboradores.show', colaborador.id)} className="text-sm text-primary hover:underline">
                {colaborador.nombres} {colaborador.apellidos} — {colaborador.cedula}
            </Link>
        );
    }

    return <p className="text-sm text-foreground">{textoAlterno || '—'}</p>;
}

export default function AciShow({ aci }: { aci: AciDetalle }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Seguridad', href: '/modules/seguridad' },
        { title: 'ACIS', href: '/modules/seguridad/acis' },
        { title: 'Reportes ACI', href: '/modules/seguridad/acis' },
        { title: aci.folio, href: `/modules/seguridad/acis/${aci.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`ACI ${aci.folio}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <HeadingSmall title={`Reporte ACI — ${aci.folio}`} description="Detalle completo del acto o condición insegura." />
                    {aci.estatus_asignacion && <Badge variant="secondary">{aci.estatus_asignacion}</Badge>}
                </div>

                <SeccionCard icon={ClipboardList} titulo="Datos del reporte" tono="verde">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Campo label="Folio" valor={aci.folio} />
                        <Campo label="Reporte de" valor={aci.reporte_de} />
                        <Campo label="Fecha que se alzó" valor={formatearFecha(aci.fecha_alzado)} />
                        <Campo label="Hora que se alzó" valor={aci.hora_alzado} />
                        <Campo label="Fecha del incidente" valor={formatearFecha(aci.fecha_incidente)} />
                        <Campo label="Hora del incidente" valor={aci.hora_incidente} />
                        <Campo label="Compañía" valor={aci.compania} />
                        <Campo label="BU" valor={aci.bu} />
                    </div>
                </SeccionCard>

                <SeccionCard icon={Users} titulo="Personas involucradas" tono="azul">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p className="text-xs font-medium text-muted-foreground">Reportado por</p>
                            <ColaboradorLink colaborador={aci.colaborador} textoAlterno={aci.reportado_por} />
                            {aci.qr_reportante && <p className="text-xs text-muted-foreground">QR SKAP: {aci.qr_reportante}</p>}
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground">Persona que lo cometió</p>
                            <p className="text-sm text-foreground">{aci.persona_cometio ?? '—'}</p>
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground">Asignado por</p>
                            <ColaboradorLink colaborador={aci.asignador} textoAlterno={aci.asignado_por} />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground">Asignado a</p>
                            <ColaboradorLink colaborador={aci.asignado} textoAlterno={aci.asignado_a} />
                        </div>
                        <Campo label="Cerrado por" valor={aci.cerrado_por} />
                        <Campo label="PIN de quien lo cometió" valor={aci.persona_cometio_pin} />
                        <Campo label="Nº empleado de quien lo cometió" valor={aci.persona_cometio_numero_empleado} />
                    </div>
                </SeccionCard>

                <SeccionCard icon={MapPin} titulo="Ubicación y clasificación" tono="verde">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Campo label="Ubicación" valor={aci.ubicacion} />
                        <Campo label="Dentro/fuera de ubicación" valor={aci.dentro_fuera_ubicacion} />
                        <Campo label="Zona" valor={aci.zona} />
                        <Campo label="Subzona" valor={aci.subzona} />
                        <Campo label="Área" valor={aci.area} />
                        <Campo label="Subárea" valor={aci.subarea} />
                        <Campo label="Clasificación" valor={aci.clasificacion} />
                        <Campo label="Tipo de riesgo" valor={aci.tipo_riesgo} />
                        <Campo label="Ciudad elegida" valor={aci.ciudad_elegida} />
                        <Campo label="Estado elegido" valor={aci.estado_elegido} />
                        <Campo label="Tipo de acto en ruta" valor={aci.tipo_acto_ruta} />
                        <Campo label="Fue por externo" valor={aci.fue_por_externo === null ? '—' : aci.fue_por_externo ? 'Sí' : 'No'} />
                        <Campo label="SIF" valor={aci.sif === null ? '—' : aci.sif ? 'Sí' : 'No'} />
                    </div>
                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                        <Campo label="Descripción" valor={aci.descripcion} />
                        <Campo label="Posible solución" valor={aci.posible_solucion} />
                        <Campo label="Descripción de la ubicación" valor={aci.descripcion_ubicacion} />
                        <Campo label="Descripción del tipo de riesgo" valor={aci.descripcion_tipo_riesgo} />
                    </div>
                </SeccionCard>

                <SeccionCard icon={CalendarClock} titulo="Asignación y cierre" tono="azul">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Campo label="Estatus de asignación" valor={aci.estatus_asignacion} />
                        <Campo label="Fecha y hora de asignación" valor={formatearFechaHora(aci.fecha_hora_asignacion)} />
                        <Campo label="Fecha y hora de cierre" valor={formatearFechaHora(aci.fecha_hora_cierre)} />
                        <Campo label="Fecha pospuesto" valor={formatearFecha(aci.fecha_pospuesto)} />
                    </div>
                </SeccionCard>

                {aci.datos_adicionales && Object.keys(aci.datos_adicionales).length > 0 && (
                    <SeccionCard icon={ShieldAlert} titulo="Datos adicionales" subtitulo="Columnas del Excel no reconocidas" tono="neutral">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {Object.entries(aci.datos_adicionales).map(([clave, valor]) => (
                                <Campo key={clave} label={clave} valor={valor} />
                            ))}
                        </div>
                    </SeccionCard>
                )}
            </div>
        </AppLayout>
    );
}
