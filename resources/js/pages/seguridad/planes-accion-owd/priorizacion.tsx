import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Seguridad', href: '/modules/seguridad' },
    { title: 'Evaluaciones OWD', href: '/modules/seguridad/evaluaciones-owd' },
    { title: 'Planes de Acción', href: '/modules/seguridad/planes-accion-owd' },
    { title: 'Priorización', href: '/modules/seguridad/planes-accion-owd/priorizacion' },
];

interface Fila {
    id: number;
    nombres: string;
    apellidos: string;
    cedula: string;
    porcentaje: number;
    preguntas_no_conformes: number;
}

export default function PriorizacionPlanesAccionOwd({ priorizacion }: { priorizacion: Fila[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Priorización de planes de acción OWD" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <HeadingSmall
                    title="Priorización de gestión"
                    description="Colaboradores que no cumplen el OWD y tienen preguntas incumplidas con plan de acción, ordenados de peor a mejor cumplimiento."
                />

                <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>#</TableHead>
                                <TableHead>Colaborador</TableHead>
                                <TableHead>Cumplimiento OWD</TableHead>
                                <TableHead>Preguntas no conformes</TableHead>
                                <TableHead className="text-right">Detalle</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {priorizacion.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="py-6 text-center text-muted-foreground">
                                        No hay colaboradores pendientes de priorizar.
                                    </TableCell>
                                </TableRow>
                            )}
                            {priorizacion.map((fila, index) => (
                                <TableRow key={fila.id}>
                                    <TableCell className="text-muted-foreground">{index + 1}</TableCell>
                                    <TableCell>
                                        {fila.nombres} {fila.apellidos} — {fila.cedula}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="destructive">{fila.porcentaje}%</Badge>
                                    </TableCell>
                                    <TableCell>{fila.preguntas_no_conformes}</TableCell>
                                    <TableCell className="text-right">
                                        <Link
                                            href={route('seguridad.evaluaciones-owd.cumplimiento.show', fila.id)}
                                            className="text-sm text-primary hover:underline"
                                        >
                                            Ver detalle
                                        </Link>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}
