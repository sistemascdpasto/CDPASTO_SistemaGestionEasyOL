import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Encuesta de Morbilidad', href: '/portal/encuesta-morbilidad' },
    { title: 'Mi historial', href: '/portal/encuesta-morbilidad/historial' },
];

interface EncuestaFila {
    id: number;
    estado: string;
    fecha_hora: string;
    enviado_en: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface EncuestasPaginator {
    data: EncuestaFila[];
    links: PaginationLink[];
}

export default function EncuestaMorbilidadHistorial({ encuestas }: { encuestas: EncuestasPaginator }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mi historial — Encuesta de Morbilidad" />
            <div className="mx-auto flex h-full w-full max-w-3xl flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <HeadingSmall title="Mi historial" description="Tus encuestas de morbilidad sentida, completadas o en borrador." />
                    <Button size="sm" asChild>
                        <Link href={route('portal.encuesta-morbilidad')}>Responder encuesta</Link>
                    </Button>
                </div>

                <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Fecha de inicio</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Enviada</TableHead>
                                <TableHead className="text-right">Detalle</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {encuestas.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="py-6 text-center text-muted-foreground">
                                        Todavía no has diligenciado ninguna encuesta.
                                    </TableCell>
                                </TableRow>
                            )}
                            {encuestas.data.map((encuesta) => (
                                <TableRow key={encuesta.id}>
                                    <TableCell>{new Date(encuesta.fecha_hora).toLocaleString()}</TableCell>
                                    <TableCell>
                                        <Badge variant={encuesta.estado === 'completada' ? 'default' : 'secondary'}>
                                            {encuesta.estado === 'completada' ? 'Completada' : 'Borrador'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{encuesta.enviado_en ? new Date(encuesta.enviado_en).toLocaleString() : '—'}</TableCell>
                                    <TableCell className="text-right">
                                        {encuesta.estado === 'completada' ? (
                                            <Link
                                                href={route('portal.encuesta-morbilidad.show', encuesta.id)}
                                                className="text-sm text-primary hover:underline"
                                            >
                                                Ver detalle
                                            </Link>
                                        ) : (
                                            <Link href={route('portal.encuesta-morbilidad')} className="text-sm text-primary hover:underline">
                                                Continuar
                                            </Link>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {encuestas.links.length > 3 && (
                    <div className="flex flex-wrap gap-1">
                        {encuestas.links.map((link, index) => (
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
