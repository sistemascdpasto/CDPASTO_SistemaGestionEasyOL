import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Alertas', href: '/portal/alertas' },
];

const TIPO_LABELS: Record<string, string> = {
    prueba_positiva: 'Prueba positiva',
    salud_mala: 'Salud: Malo',
    no_apto: 'No apto para laborar',
    calibracion_proxima: 'Calibración próxima',
    certificado_vencido: 'Certificado por vencer',
};

interface AlertaRow {
    id: number;
    tipo: string;
    mensaje: string;
    atendida: boolean;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface AlertasPaginator {
    data: AlertaRow[];
    links: PaginationLink[];
}

export default function ColaboradorAlertas({ alertas }: { alertas: AlertasPaginator }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Alertas" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <HeadingSmall title="Mis alertas" description="Notificaciones generadas a partir de tus pruebas y condición de salud." />

                <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Fecha</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Mensaje</TableHead>
                                <TableHead>Estado</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {alertas.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="text-muted-foreground py-6 text-center">
                                        No tienes alertas registradas.
                                    </TableCell>
                                </TableRow>
                            )}
                            {alertas.data.map((alerta) => (
                                <TableRow key={alerta.id}>
                                    <TableCell>{new Date(alerta.created_at).toLocaleString()}</TableCell>
                                    <TableCell>
                                        <Badge variant={alerta.atendida ? 'secondary' : 'destructive'}>
                                            {TIPO_LABELS[alerta.tipo] ?? alerta.tipo}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="max-w-md">{alerta.mensaje}</TableCell>
                                    <TableCell>{alerta.atendida ? 'Atendida' : 'Pendiente'}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {alertas.links.length > 3 && (
                    <div className="flex flex-wrap gap-1">
                        {alertas.links.map((link, index) => (
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
