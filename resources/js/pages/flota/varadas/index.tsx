import { VaradaHeatmap, type PuntoMapa } from '@/components/flota/varada-heatmap';
import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogClose, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { KpiCard, KpiCardGrid } from '@/components/kpi-card';
import { MUNICIPIOS_NARINO } from '@/data/municipios-narino';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Clock,
    FileSpreadsheet,
    Gauge,
    MapPinOff,
    Pencil,
    Plus,
    Repeat,
    Trash2,
    Upload,
    Wrench,
} from 'lucide-react';
import { FormEventHandler, useMemo, useState } from 'react';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Flota', href: '/modules/flota' },
    { title: 'Control de Varadas', href: '/modules/flota/varadas' },
];

const MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

const COLOR_PRIMARIO = '#2B6CB0';
const COLOR_ALERTA = '#d03b3b';
const COLOR_ADVERTENCIA = '#fab219';
const TODOS = 'todos';

const SISTEMAS_FIJOS = [
    'CARROCERIA',
    'ELECTRICO',
    'FLUIDOS',
    'FRENOS',
    'INYECCION',
    'LLANTAS',
    'MOTOR',
    'REFRIGERACION',
    'TELEMETRIA',
    'TRANSMISION',
];
const SISTEMA_OTRO = '__otro__';

interface VaradaRow {
    id: number;
    placa: string;
    fecha_reportada: string;
    fecha_asistencia: string | null;
    fecha_solucion: string | null;
    sistema: string | null;
    tipo_falla: string | null;
    descripcion: string | null;
    causa_probable: string | null;
    repetitiva: boolean;
    ruta: string | null;
    lugar: string | null;
    proveedor: string | null;
    tipo_solucion: string | null;
    impacto: string | null;
    gravedad: number | null;
    observaciones: string | null;
    latitud: number | null;
    longitud: number | null;
    origen: 'excel' | 'manual';
    tfs_horas: number | null;
    dias_fs: number | null;
    gt_horas: number | null;
    esta_abierta: boolean;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface VaradasPaginator {
    data: VaradaRow[];
    links: PaginationLink[];
    from: number | null;
}

interface Filters {
    anio: number | null;
    mes: number | null;
    placa: string;
    sistema: string;
    fecha_desde: string;
    fecha_hasta: string;
}

interface Catalogos {
    placas: string[];
    sistemas: string[];
    anios: number[];
    placas_flota: string[];
}

interface Resumen {
    total: number;
    abiertas: number;
    pct_repetitivas: number;
    promedio_tfs_horas: number | null;
    promedio_gt_horas: number | null;
    promedio_gravedad: number;
    sin_coordenadas: number;
}

interface Indicadores {
    resumen: Resumen;
    por_mes: { mes: string; total: number }[];
    dias_fs_por_placa: { placa: string; total: number }[];
    dias_fs_por_sistema: { sistema: string; total: number }[];
    por_ruta: { ruta: string; total: number }[];
    top_causas: { causa: string; total: number }[];
    top_proveedores: { proveedor: string; total: number }[];
    distribucion_gravedad: { gravedad: number; total: number }[];
    horas_promedio_por_sistema: { sistema: string; horas: number }[];
}

type Vista = 'indicadores' | 'registros';

function formatMesEtiqueta(clave: string): string {
    const [anio, mes] = clave.split('-');
    return `${MESES[parseInt(mes, 10) - 1]?.slice(0, 3) ?? mes} ${anio}`;
}

function formatFecha(fecha: string | null): string {
    if (!fecha) return '—';
    return new Date(fecha).toLocaleString('es-CO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}


function EmptyChart({ children }: { children: React.ReactNode }) {
    return <p className="py-8 text-center text-sm text-muted-foreground">{children}</p>;
}

const emptyForm = {
    placa: '',
    fecha_reportada: '',
    fecha_asistencia: '',
    fecha_solucion: '',
    sistema: '',
    tipo_falla: '',
    descripcion: '',
    causa_probable: '',
    repetitiva: false,
    ruta: '',
    lugar: '',
    proveedor: '',
    tipo_solucion: '',
    impacto: '',
    gravedad: '',
    observaciones: '',
    latitud: '',
    longitud: '',
};

export default function VaradasIndex({
    registros,
    filters,
    catalogos,
    indicadores,
    mapa_puntos,
}: {
    registros: VaradasPaginator;
    filters: Filters;
    catalogos: Catalogos;
    indicadores: Indicadores;
    mapa_puntos: PuntoMapa[];
}) {
    const { flash } = usePage<{ flash?: { status?: { message: string; type: string } } }>().props;
    const [vista, setVista] = useState<Vista>('indicadores');
    const [openImportModal, setOpenImportModal] = useState(false);
    const [openFormModal, setOpenFormModal] = useState(false);
    const [editing, setEditing] = useState<VaradaRow | null>(null);
    const [sistemaOtro, setSistemaOtro] = useState(false);

    const applyFilters = (overrides: Partial<Filters>) => {
        const next = { ...filters, ...overrides };
        router.get(
            route('flota.varadas.index'),
            {
                anio: next.anio ?? undefined,
                mes: next.mes ?? undefined,
                placa: next.placa,
                sistema: next.sistema,
                fecha_desde: next.fecha_desde,
                fecha_hasta: next.fecha_hasta,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const {
        data: importData,
        setData: setImportData,
        post: postImport,
        processing: importing,
        reset: resetImport,
    } = useForm({ archivos: [] as File[] });

    const handleImportSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        postImport(route('flota.varadas.importar'), {
            forceFormData: true,
            onSuccess: () => {
                resetImport();
                setOpenImportModal(false);
            },
        });
    };

    const {
        data: formData,
        setData: setFormData,
        post: postForm,
        put: putForm,
        processing: saving,
        reset: resetForm,
        errors: formErrors,
    } = useForm(emptyForm);

    const openCreate = () => {
        setEditing(null);
        resetForm();
        setSistemaOtro(false);
        setOpenFormModal(true);
    };

    const openEdit = (row: VaradaRow) => {
        setEditing(row);
        setSistemaOtro(row.sistema !== null && row.sistema !== '' && !SISTEMAS_FIJOS.includes(row.sistema));
        setFormData({
            placa: row.placa,
            fecha_reportada: row.fecha_reportada ? row.fecha_reportada.slice(0, 16) : '',
            fecha_asistencia: row.fecha_asistencia ? row.fecha_asistencia.slice(0, 16) : '',
            fecha_solucion: row.fecha_solucion ? row.fecha_solucion.slice(0, 16) : '',
            sistema: row.sistema ?? '',
            tipo_falla: row.tipo_falla ?? '',
            descripcion: row.descripcion ?? '',
            causa_probable: row.causa_probable ?? '',
            repetitiva: row.repetitiva,
            ruta: row.ruta ?? '',
            lugar: row.lugar ?? '',
            proveedor: row.proveedor ?? '',
            tipo_solucion: row.tipo_solucion ?? '',
            impacto: row.impacto ?? '',
            gravedad: row.gravedad ? String(row.gravedad) : '',
            observaciones: row.observaciones ?? '',
            latitud: row.latitud ? String(row.latitud) : '',
            longitud: row.longitud ? String(row.longitud) : '',
        });
        setOpenFormModal(true);
    };

    const submitForm: FormEventHandler = (e) => {
        e.preventDefault();
        const onSuccess = () => {
            resetForm();
            setOpenFormModal(false);
            setEditing(null);
        };
        if (editing) {
            putForm(route('flota.varadas.update', editing.id), { onSuccess });
        } else {
            postForm(route('flota.varadas.store'), { onSuccess });
        }
    };

    // Si se está editando una varada cuyo vehículo ya no está activo en
    // Flota, su placa no aparecería en catalogos.placas_flota: se agrega
    // igual para no dejar el selector vacío/roto al abrir el formulario.
    const placaOptions = useMemo(() => {
        if (editing && editing.placa && !catalogos.placas_flota.includes(editing.placa)) {
            return [editing.placa, ...catalogos.placas_flota];
        }
        return catalogos.placas_flota;
    }, [catalogos.placas_flota, editing]);

    const handleSistemaChange = (value: string) => {
        if (value === SISTEMA_OTRO) {
            setSistemaOtro(true);
            if (SISTEMAS_FIJOS.includes(formData.sistema)) {
                setFormData('sistema', '');
            }
            return;
        }
        setSistemaOtro(false);
        setFormData('sistema', value);
    };

    const handleLugarChange = (value: string) => {
        const municipio = MUNICIPIOS_NARINO.find((m) => m.lugar === value);
        setFormData((data) => ({
            ...data,
            lugar: value,
            latitud: municipio ? String(municipio.latitud) : data.latitud,
            longitud: municipio ? String(municipio.longitud) : data.longitud,
        }));
    };

    const handleDelete = (row: VaradaRow) => {
        if (confirm(`¿Eliminar la varada de ${row.placa} del ${formatFecha(row.fecha_reportada)}?`)) {
            router.delete(route('flota.varadas.destroy', row.id));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Control de Varadas" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <HeadingSmall
                        title="Control de Varadas"
                        description="Gestión y trazabilidad de varadas de la flota — carga desde Excel o registro manual."
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="flex rounded-md border border-sidebar-border/70 p-1 dark:border-sidebar-border">
                            <Button type="button" variant={vista === 'indicadores' ? 'default' : 'ghost'} size="sm" onClick={() => setVista('indicadores')}>
                                Indicadores
                            </Button>
                            <Button type="button" variant={vista === 'registros' ? 'default' : 'ghost'} size="sm" onClick={() => setVista('registros')}>
                                Registros
                            </Button>
                        </div>
                        <Button type="button" size="sm" onClick={openCreate}>
                            <Plus className="mr-1 size-4" />
                            Registrar varada
                        </Button>
                        <Button type="button" variant="outline" size="sm" onClick={() => setOpenImportModal(true)}>
                            <Upload className="mr-1 size-4" />
                            Importar Excel
                        </Button>
                    </div>
                </div>

                {flash?.status && (
                    <div
                        className={`flex items-center gap-2 rounded-lg border p-4 text-sm font-medium shadow-xs ${
                            flash.status.type === 'success'
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
                                : 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950/60 dark:text-rose-300'
                        }`}
                    >
                        {flash.status.type === 'success' ? <CheckCircle2 className="size-5" /> : <AlertTriangle className="size-5" />}
                        <span>{flash.status.message}</span>
                    </div>
                )}

                <div className="flex flex-wrap items-end gap-4 rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="grid gap-1">
                        <Label className="text-xs text-muted-foreground">Año</Label>
                        <Select
                            value={filters.anio ? String(filters.anio) : TODOS}
                            onValueChange={(v) => applyFilters({ anio: v === TODOS ? null : Number(v) })}
                        >
                            <SelectTrigger className="w-32">
                                <SelectValue placeholder="Año" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={TODOS}>Todos</SelectItem>
                                {catalogos.anios.map((anio) => (
                                    <SelectItem key={anio} value={String(anio)}>
                                        {anio}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="grid gap-1">
                        <Label className="text-xs text-muted-foreground">Mes</Label>
                        <Select
                            value={filters.mes ? String(filters.mes) : TODOS}
                            onValueChange={(v) => applyFilters({ mes: v === TODOS ? null : Number(v) })}
                        >
                            <SelectTrigger className="w-36">
                                <SelectValue placeholder="Mes" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={TODOS}>Todos</SelectItem>
                                {MESES.map((mes, idx) => (
                                    <SelectItem key={mes} value={String(idx + 1)}>
                                        {mes}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="grid gap-1">
                        <Label className="text-xs text-muted-foreground">Placa</Label>
                        <Select value={filters.placa || TODOS} onValueChange={(v) => applyFilters({ placa: v === TODOS ? '' : v })}>
                            <SelectTrigger className="w-32">
                                <SelectValue placeholder="Placa" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={TODOS}>Todas</SelectItem>
                                {catalogos.placas.map((placa) => (
                                    <SelectItem key={placa} value={placa}>
                                        {placa}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="grid gap-1">
                        <Label className="text-xs text-muted-foreground">Sistema</Label>
                        <Select value={filters.sistema || TODOS} onValueChange={(v) => applyFilters({ sistema: v === TODOS ? '' : v })}>
                            <SelectTrigger className="w-40">
                                <SelectValue placeholder="Sistema" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={TODOS}>Todos</SelectItem>
                                {catalogos.sistemas.map((sistema) => (
                                    <SelectItem key={sistema} value={sistema}>
                                        {sistema}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
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
                    {(filters.anio || filters.mes || filters.placa !== '' || filters.sistema !== '' || filters.fecha_desde !== '' || filters.fecha_hasta !== '') && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => applyFilters({ anio: null, mes: null, placa: '', sistema: '', fecha_desde: '', fecha_hasta: '' })}
                        >
                            Limpiar filtros
                        </Button>
                    )}
                </div>

                {vista === 'indicadores' ? (
                    <div className="flex flex-col gap-6">
                        <KpiCardGrid className="sm:grid-cols-2 lg:grid-cols-6">
                            <KpiCard label="Total varadas" value={indicadores.resumen.total} icon={Wrench} color={COLOR_PRIMARIO} />
                            <KpiCard label="Abiertas" value={indicadores.resumen.abiertas} icon={AlertTriangle} color={COLOR_ALERTA} />
                            <KpiCard label="% Repetitivas" value={indicadores.resumen.pct_repetitivas} suffix="%" decimals={1} icon={Repeat} color={COLOR_ADVERTENCIA} />
                            <KpiCard
                                label="Prom. TFS (horas)"
                                value={indicadores.resumen.promedio_tfs_horas ?? '—'}
                                decimals={2}
                                icon={Clock}
                                color={COLOR_PRIMARIO}
                            />
                            <KpiCard
                                label="Prom. gestión (horas)"
                                value={indicadores.resumen.promedio_gt_horas ?? '—'}
                                decimals={2}
                                icon={Clock}
                                color={COLOR_PRIMARIO}
                            />
                            <KpiCard label="Gravedad promedio" value={indicadores.resumen.promedio_gravedad} decimals={1} icon={Gauge} color={COLOR_ADVERTENCIA} />
                        </KpiCardGrid>

                        <div className="grid gap-4 lg:grid-cols-2">
                            <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Cant. asistencias por mes</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {indicadores.por_mes.length === 0 ? (
                                        <EmptyChart>Todavía no hay varadas registradas.</EmptyChart>
                                    ) : (
                                        <ResponsiveContainer width="100%" height={280}>
                                            <BarChart data={indicadores.por_mes}>
                                                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                                <XAxis dataKey="mes" tickFormatter={formatMesEtiqueta} tick={{ fontSize: 12 }} />
                                                <YAxis allowDecimals={false} tick={{ fontSize: 12 }} />
                                                <Tooltip labelFormatter={(label) => formatMesEtiqueta(String(label))} />
                                                <Bar dataKey="total" name="Varadas" fill={COLOR_PRIMARIO} radius={[4, 4, 0, 0]} />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Cant. varadas por ruta</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {indicadores.por_ruta.length === 0 ? (
                                        <EmptyChart>Todavía no hay varadas registradas.</EmptyChart>
                                    ) : (
                                        <ResponsiveContainer width="100%" height={Math.max(280, indicadores.por_ruta.length * 28)}>
                                            <BarChart data={indicadores.por_ruta} layout="vertical" margin={{ left: 8, right: 16 }}>
                                                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" horizontal={false} />
                                                <XAxis type="number" allowDecimals={false} tick={{ fontSize: 12 }} />
                                                <YAxis type="category" dataKey="ruta" width={110} tick={{ fontSize: 11 }} interval={0} />
                                                <Tooltip />
                                                <Bar dataKey="total" name="Varadas" fill={COLOR_PRIMARIO} radius={[0, 4, 4, 0]} />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Días FS por placa</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {indicadores.dias_fs_por_placa.length === 0 ? (
                                        <EmptyChart>Sin varadas resueltas todavía.</EmptyChart>
                                    ) : (
                                        <ResponsiveContainer width="100%" height={280}>
                                            <BarChart data={indicadores.dias_fs_por_placa}>
                                                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                                <XAxis dataKey="placa" tick={{ fontSize: 12 }} interval={0} angle={-20} textAnchor="end" height={50} />
                                                <YAxis tick={{ fontSize: 12 }} />
                                                <Tooltip />
                                                <Bar dataKey="total" name="Días FS" fill={COLOR_ADVERTENCIA} radius={[4, 4, 0, 0]} />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Días FS por sistema</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {indicadores.dias_fs_por_sistema.length === 0 ? (
                                        <EmptyChart>Sin varadas resueltas todavía.</EmptyChart>
                                    ) : (
                                        <ResponsiveContainer width="100%" height={280}>
                                            <BarChart data={indicadores.dias_fs_por_sistema}>
                                                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                                <XAxis dataKey="sistema" tick={{ fontSize: 11 }} interval={0} angle={-20} textAnchor="end" height={60} />
                                                <YAxis tick={{ fontSize: 12 }} />
                                                <Tooltip />
                                                <Bar dataKey="total" name="Días FS" fill={COLOR_ALERTA} radius={[4, 4, 0, 0]} />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Mapa de calor de varadas</CardTitle>
                                {indicadores.resumen.sin_coordenadas > 0 && (
                                    <Badge variant="outline" className="gap-1 text-muted-foreground">
                                        <MapPinOff className="size-3" />
                                        {indicadores.resumen.sin_coordenadas} sin coordenadas
                                    </Badge>
                                )}
                            </CardHeader>
                            <CardContent>
                                {mapa_puntos.length === 0 ? (
                                    <EmptyChart>Ninguna varada del filtro actual tiene coordenadas resueltas todavía.</EmptyChart>
                                ) : (
                                    <VaradaHeatmap puntos={mapa_puntos} />
                                )}
                            </CardContent>
                        </Card>

                        <div className="grid gap-4 lg:grid-cols-2">
                            <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Top causas probables</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {indicadores.top_causas.length === 0 ? (
                                        <EmptyChart>Todavía no hay varadas registradas.</EmptyChart>
                                    ) : (
                                        <ResponsiveContainer width="100%" height={Math.max(220, indicadores.top_causas.length * 30)}>
                                            <BarChart data={indicadores.top_causas} layout="vertical" margin={{ left: 8, right: 16 }}>
                                                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" horizontal={false} />
                                                <XAxis type="number" allowDecimals={false} tick={{ fontSize: 12 }} />
                                                <YAxis type="category" dataKey="causa" width={190} tick={{ fontSize: 10 }} interval={0} />
                                                <Tooltip />
                                                <Bar dataKey="total" name="Varadas" fill={COLOR_PRIMARIO} radius={[0, 4, 4, 0]} />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Top proveedores</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {indicadores.top_proveedores.length === 0 ? (
                                        <EmptyChart>Todavía no hay varadas registradas.</EmptyChart>
                                    ) : (
                                        <ResponsiveContainer width="100%" height={Math.max(220, indicadores.top_proveedores.length * 30)}>
                                            <BarChart data={indicadores.top_proveedores} layout="vertical" margin={{ left: 8, right: 16 }}>
                                                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" horizontal={false} />
                                                <XAxis type="number" allowDecimals={false} tick={{ fontSize: 12 }} />
                                                <YAxis type="category" dataKey="proveedor" width={140} tick={{ fontSize: 11 }} interval={0} />
                                                <Tooltip />
                                                <Bar dataKey="total" name="Varadas" fill={COLOR_PRIMARIO} radius={[0, 4, 4, 0]} />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Distribución de gravedad</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {indicadores.distribucion_gravedad.length === 0 ? (
                                        <EmptyChart>Todavía no hay varadas con gravedad registrada.</EmptyChart>
                                    ) : (
                                        <ResponsiveContainer width="100%" height={220}>
                                            <BarChart data={indicadores.distribucion_gravedad}>
                                                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                                <XAxis dataKey="gravedad" tick={{ fontSize: 12 }} />
                                                <YAxis allowDecimals={false} tick={{ fontSize: 12 }} />
                                                <Tooltip />
                                                <Bar dataKey="total" name="Varadas" fill={COLOR_ADVERTENCIA} radius={[4, 4, 0, 0]} />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">Horas promedio de atención por sistema</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {indicadores.horas_promedio_por_sistema.length === 0 ? (
                                        <EmptyChart>Sin varadas resueltas todavía.</EmptyChart>
                                    ) : (
                                        <ResponsiveContainer width="100%" height={220}>
                                            <BarChart data={indicadores.horas_promedio_por_sistema}>
                                                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                                <XAxis dataKey="sistema" tick={{ fontSize: 11 }} interval={0} angle={-20} textAnchor="end" height={60} />
                                                <YAxis tick={{ fontSize: 12 }} />
                                                <Tooltip />
                                                <Bar dataKey="horas" name="Horas promedio" fill={COLOR_PRIMARIO} radius={[4, 4, 0, 0]} />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                ) : (
                    <div className="flex flex-col gap-4">
                        <div className="overflow-x-auto rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Placa</TableHead>
                                        <TableHead>Reportada</TableHead>
                                        <TableHead>Sistema</TableHead>
                                        <TableHead>Falla</TableHead>
                                        <TableHead>Ruta</TableHead>
                                        <TableHead>Días FS</TableHead>
                                        <TableHead>Repetitiva</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Origen</TableHead>
                                        <TableHead className="text-right">Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {registros.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={10} className="py-6 text-center text-muted-foreground">
                                                No se encontraron varadas con los filtros actuales.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {registros.data.map((row) => (
                                        <TableRow key={row.id} className={row.esta_abierta ? 'bg-destructive/5' : undefined}>
                                            <TableCell className="font-medium">{row.placa}</TableCell>
                                            <TableCell>{formatFecha(row.fecha_reportada)}</TableCell>
                                            <TableCell>{row.sistema ?? '—'}</TableCell>
                                            <TableCell className="max-w-52 truncate" title={row.tipo_falla ?? ''}>
                                                {row.tipo_falla ?? '—'}
                                            </TableCell>
                                            <TableCell>{row.ruta ?? '—'}</TableCell>
                                            <TableCell>{row.dias_fs ?? '—'}</TableCell>
                                            <TableCell>
                                                {row.repetitiva ? (
                                                    <Badge variant="destructive">Sí</Badge>
                                                ) : (
                                                    <Badge variant="outline" className="text-muted-foreground">
                                                        No
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {row.esta_abierta ? (
                                                    <Badge variant="destructive">Abierta</Badge>
                                                ) : (
                                                    <Badge variant="secondary">Solucionada</Badge>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline" className="gap-1 text-muted-foreground">
                                                    {row.origen === 'excel' ? <FileSpreadsheet className="size-3" /> : <Pencil className="size-3" />}
                                                    {row.origen === 'excel' ? 'Excel' : 'Manual'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button type="button" variant="ghost" size="icon" onClick={() => openEdit(row)} aria-label="Editar">
                                                        <Pencil className="size-4" />
                                                    </Button>
                                                    <Button type="button" variant="ghost" size="icon" onClick={() => handleDelete(row)} aria-label="Eliminar">
                                                        <Trash2 className="size-4 text-destructive" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {registros.links.length > 3 && (
                            <div className="flex flex-wrap gap-1">
                                {registros.links.map((link, index) => (
                                    <Button key={index} variant={link.active ? 'default' : 'outline'} size="sm" disabled={!link.url} asChild={!!link.url}>
                                        {link.url ? (
                                            <Link href={link.url} preserveScroll preserveState dangerouslySetInnerHTML={{ __html: link.label }} />
                                        ) : (
                                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                        )}
                                    </Button>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                <Dialog open={openImportModal} onOpenChange={setOpenImportModal}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <FileSpreadsheet className="size-5 text-emerald-600" />
                                Importar Excel de varadas
                            </DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleImportSubmit} className="flex flex-col gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="archivos">Archivo (.xlsx, .xls, .xlsm, .csv)</Label>
                                <Input
                                    id="archivos"
                                    type="file"
                                    accept=".xlsx,.xls,.xlsm,.csv"
                                    className="rounded-lg border p-1 text-xs file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100"
                                    onChange={(e) => setImportData('archivos', Array.from(e.target.files ?? []))}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Debe incluir la hoja de varadas (con columnas Placa/Sistema) y la hoja de coordenadas (Lugar/Latitud/Longitud).
                                </p>
                            </div>
                            <DialogFooter className="gap-2 sm:gap-0">
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancelar
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={importing || importData.archivos.length === 0}>
                                    {importing ? 'Importando…' : 'Importar'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                <Dialog open={openFormModal} onOpenChange={setOpenFormModal}>
                    <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>{editing ? 'Editar varada' : 'Registrar varada'}</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={submitForm} className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-1.5">
                                <Label>Placa</Label>
                                <Select value={formData.placa} onValueChange={(value) => setFormData('placa', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona la placa" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {placaOptions.map((placa) => (
                                            <SelectItem key={placa} value={placa}>
                                                {placa}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-muted-foreground">Vehículos activos en Documentación de Flota.</p>
                                {formErrors.placa && <p className="text-xs text-destructive">{formErrors.placa}</p>}
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Sistema</Label>
                                <Select value={sistemaOtro ? SISTEMA_OTRO : formData.sistema} onValueChange={handleSistemaChange}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona el sistema" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {SISTEMAS_FIJOS.map((sistema) => (
                                            <SelectItem key={sistema} value={sistema}>
                                                {sistema}
                                            </SelectItem>
                                        ))}
                                        <SelectItem value={SISTEMA_OTRO}>Otro</SelectItem>
                                    </SelectContent>
                                </Select>
                                {sistemaOtro && (
                                    <Input
                                        value={formData.sistema}
                                        onChange={(e) => setFormData('sistema', e.target.value)}
                                        placeholder="Escribe el sistema"
                                        className="mt-1"
                                    />
                                )}
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Fecha y hora reportada</Label>
                                <Input
                                    type="datetime-local"
                                    value={formData.fecha_reportada}
                                    onChange={(e) => setFormData('fecha_reportada', e.target.value)}
                                    required
                                />
                                {formErrors.fecha_reportada && <p className="text-xs text-destructive">{formErrors.fecha_reportada}</p>}
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Fecha y hora asistencia</Label>
                                <Input
                                    type="datetime-local"
                                    value={formData.fecha_asistencia}
                                    onChange={(e) => setFormData('fecha_asistencia', e.target.value)}
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Fecha y hora solución</Label>
                                <Input
                                    type="datetime-local"
                                    value={formData.fecha_solucion}
                                    onChange={(e) => setFormData('fecha_solucion', e.target.value)}
                                />
                                {formErrors.fecha_solucion && <p className="text-xs text-destructive">{formErrors.fecha_solucion}</p>}
                                <p className="text-xs text-muted-foreground">Vacío = varada todavía abierta.</p>
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Tipo de falla</Label>
                                <Input value={formData.tipo_falla} onChange={(e) => setFormData('tipo_falla', e.target.value)} />
                            </div>
                            <div className="grid gap-1.5 sm:col-span-2">
                                <Label>Descripción</Label>
                                <Textarea value={formData.descripcion} onChange={(e) => setFormData('descripcion', e.target.value)} rows={2} />
                            </div>
                            <div className="grid gap-1.5 sm:col-span-2">
                                <Label>Causa probable</Label>
                                <Textarea value={formData.causa_probable} onChange={(e) => setFormData('causa_probable', e.target.value)} rows={2} />
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Ruta</Label>
                                <Input value={formData.ruta} onChange={(e) => setFormData('ruta', e.target.value)} placeholder="Ej. Pasto, Sandoná..." />
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Lugar</Label>
                                <Select value={formData.lugar} onValueChange={handleLugarChange}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona el municipio" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {MUNICIPIOS_NARINO.map((municipio) => (
                                            <SelectItem key={municipio.lugar} value={municipio.lugar}>
                                                {municipio.lugar}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-muted-foreground">Autocompleta latitud/longitud del municipio.</p>
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Proveedor</Label>
                                <Input value={formData.proveedor} onChange={(e) => setFormData('proveedor', e.target.value)} />
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Tipo de solución</Label>
                                <Input value={formData.tipo_solucion} onChange={(e) => setFormData('tipo_solucion', e.target.value)} />
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Impacto</Label>
                                <Input value={formData.impacto} onChange={(e) => setFormData('impacto', e.target.value)} />
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Gravedad (1-5)</Label>
                                <Input
                                    type="number"
                                    min={1}
                                    max={5}
                                    value={formData.gravedad}
                                    onChange={(e) => setFormData('gravedad', e.target.value)}
                                />
                            </div>
                            <div className="grid gap-1.5 sm:col-span-2">
                                <Label>Observaciones</Label>
                                <Textarea value={formData.observaciones} onChange={(e) => setFormData('observaciones', e.target.value)} rows={2} />
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Latitud (opcional)</Label>
                                <Input type="number" step="any" value={formData.latitud} onChange={(e) => setFormData('latitud', e.target.value)} />
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Longitud (opcional)</Label>
                                <Input type="number" step="any" value={formData.longitud} onChange={(e) => setFormData('longitud', e.target.value)} />
                            </div>
                            <div className="flex items-center gap-2 sm:col-span-2">
                                <Checkbox
                                    id="repetitiva"
                                    checked={formData.repetitiva}
                                    onCheckedChange={(checked) => setFormData('repetitiva', checked === true)}
                                />
                                <Label htmlFor="repetitiva" className="font-normal">
                                    Es una falla repetitiva
                                </Label>
                            </div>
                            <DialogFooter className="gap-2 sm:col-span-2 sm:gap-0">
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancelar
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={saving}>
                                    {saving ? 'Guardando…' : editing ? 'Guardar cambios' : 'Registrar'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
