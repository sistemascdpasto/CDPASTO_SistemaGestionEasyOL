import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Condición de Salud', href: '/portal/condicion-salud' },
    { title: 'Mi historial', href: '/portal/condicion-salud/historial' },
];

const ESTADO_VARIANT: Record<string, 'default' | 'secondary' | 'destructive'> = {
    Bueno: 'default',
    Regular: 'secondary',
    Malo: 'destructive',
};

interface RegistroHistorial {
    id: number;
    momento: string;
    estado: string;
    observacion: string | null;
    fecha_hora: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface RegistrosPaginator {
    data: RegistroHistorial[];
    links: PaginationLink[];
}

export default function CondicionSaludHistorial({
    registros,
    jornadaAbierta,
}: {
    registros: RegistrosPaginator;
    jornadaAbierta: boolean;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mi historial de condición de salud" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <HeadingSmall title="Mi historial de condición de salud" description="Todos los registros que has diligenciado." />

                {jornadaAbierta && (
                    <div className="flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                        <AlertTriangle className="mt-0.5 size-5 shrink-0" />
                        <div className="flex-1">
                            <p className="font-medium">Tienes una jornada sin cerrar</p>
                            <p className="text-amber-800/90 dark:text-amber-300/80">
                                Registraste tu ingreso pero todavía no tu salida, que es de carácter obligatorio.
                            </p>
                        </div>
                        <Button size="sm" asChild>
                            <Link href="/portal/condicion-salud">Registrar salida</Link>
                        </Button>
                    </div>
                )}

                <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Fecha</TableHead>
                                <TableHead>Momento</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Observación</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {registros.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="text-muted-foreground py-6 text-center">
                                        Todavía no has registrado tu condición de salud.
                                    </TableCell>
                                </TableRow>
                            )}
                            {registros.data.map((registro) => (
                                <TableRow key={registro.id}>
                                    <TableCell>{new Date(registro.fecha_hora).toLocaleString()}</TableCell>
                                    <TableCell className="capitalize">{registro.momento}</TableCell>
                                    <TableCell>
                                        <Badge variant={ESTADO_VARIANT[registro.estado] ?? 'secondary'}>{registro.estado}</Badge>
                                    </TableCell>
                                    <TableCell className="max-w-md">{registro.observacion ?? '—'}</TableCell>
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
