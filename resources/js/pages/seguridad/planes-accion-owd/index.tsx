import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { SeguimientoPlanAccionDialog } from '@/pages/seguridad/planes-accion-owd/seguimiento-dialog';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Download, ListPlus, ListTodo } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Seguridad', href: '/modules/seguridad' },
    { title: 'Evaluaciones OWD', href: '/modules/seguridad/evaluaciones-owd' },
    { title: 'Planes de Acción', href: '/modules/seguridad/planes-accion-owd' },
];

interface Plan {
    id: number;
    estado: string;
    fecha_vencimiento: string | null;
    observaciones: string | null;
    pregunta: {
        tarea: string | null;
        proceso: string | null;
        evaluacion_owd: {
            id: number;
            fecha_evaluacion: string | null;
            colaborador: { nombres: string; apellidos: string } | null;
        } | null;
    } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PlanesPaginator {
    data: Plan[];
    links: PaginationLink[];
}

interface Filtros {
    [key: string]: string | undefined;
    estado?: string;
    vencimiento?: string;
}

function badgeEstado(estado: string) {
    if (estado === 'Completado') return <Badge>Completado</Badge>;
    if (estado === 'En progreso') return <Badge variant="secondary">En progreso</Badge>;
    return <Badge variant="destructive">Pendiente</Badge>;
}

export default function PlanesAccionOwdIndex({ planes, filters }: { planes: PlanesPaginator; filters: Filtros }) {
    const cambiarFiltro = (campo: keyof Filtros, valor: string) => {
        router.get(
            route('seguridad.planes-accion-owd.index'),
            { ...filters, [campo]: valor === 'todos' ? '' : valor },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Planes de Acción OWD" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <HeadingSmall
                        title="Planes de Acción OWD"
                        description="Seguimiento a los incumplimientos que requieren un plan de acción."
                    />
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <a href={route('seguridad.planes-accion-owd.exportar')}>
                                <Download />
                                Exportar
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={route('seguridad.planes-accion-owd.priorizacion')}>
                                <ListPlus />
                                Priorización
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="flex flex-wrap gap-3">
                    <Select value={filters.estado || 'todos'} onValueChange={(v) => cambiarFiltro('estado', v)}>
                        <SelectTrigger className="w-48">
                            <SelectValue placeholder="Estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="todos">Todos los estados</SelectItem>
                            <SelectItem value="Pendiente">Pendiente</SelectItem>
                            <SelectItem value="En progreso">En progreso</SelectItem>
                            <SelectItem value="Completado">Completado</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select value={filters.vencimiento || 'todos'} onValueChange={(v) => cambiarFiltro('vencimiento', v)}>
                        <SelectTrigger className="w-56">
                            <SelectValue placeholder="Vencimiento" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="todos">Cualquier vencimiento</SelectItem>
                            <SelectItem value="proximo">Próximos a vencer (7 días)</SelectItem>
                            <SelectItem value="vencido">Vencidos</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-x-auto rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Colaborador</TableHead>
                                <TableHead>Tarea</TableHead>
                                <TableHead>Proceso</TableHead>
                                <TableHead>Fecha evaluación</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Vencimiento</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {planes.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="py-6 text-center text-muted-foreground">
                                        No hay planes de acción que coincidan con los filtros.
                                    </TableCell>
                                </TableRow>
                            )}
                            {planes.data.map((plan) => (
                                <TableRow key={plan.id}>
                                    <TableCell>
                                        {plan.pregunta?.evaluacion_owd?.colaborador
                                            ? `${plan.pregunta.evaluacion_owd.colaborador.nombres} ${plan.pregunta.evaluacion_owd.colaborador.apellidos}`
                                            : '—'}
                                    </TableCell>
                                    <TableCell className="max-w-xs truncate">{plan.pregunta?.tarea ?? '—'}</TableCell>
                                    <TableCell>{plan.pregunta?.proceso ?? '—'}</TableCell>
                                    <TableCell>
                                        {plan.pregunta?.evaluacion_owd?.fecha_evaluacion
                                            ? new Date(plan.pregunta.evaluacion_owd.fecha_evaluacion).toLocaleDateString()
                                            : '—'}
                                    </TableCell>
                                    <TableCell>{badgeEstado(plan.estado)}</TableCell>
                                    <TableCell>{plan.fecha_vencimiento ? new Date(plan.fecha_vencimiento).toLocaleDateString() : '—'}</TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end">
                                            <SeguimientoPlanAccionDialog
                                                planAccionOwdId={plan.id}
                                                trigger={
                                                    <Button variant="ghost" size="icon" title="Registrar avance">
                                                        <ListTodo className="size-4" />
                                                    </Button>
                                                }
                                            />
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {planes.links.length > 3 && (
                    <div className="flex flex-wrap gap-1">
                        {planes.links.map((link, index) => (
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
        </AppLayout>
    );
}
