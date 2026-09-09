import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { ColaboradorOption, ColaboradorSearchSelect } from '@/pages/seguridad/pruebas/colaborador-search-select';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { QRCodeSVG } from 'qrcode.react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Seguridad', href: '/modules/seguridad' },
    { title: 'ACIS', href: '/modules/seguridad/acis' },
    { title: 'Consultar QR SKAP', href: '/modules/seguridad/acis-consultar-qr' },
];

const ESTADO_TONOS: Record<string, 'default' | 'secondary' | 'destructive'> = {
    Activo: 'default',
    Inactivo: 'destructive',
    'Sin asignar': 'secondary',
};

interface ColaboradorDetalle {
    id: number;
    nombres: string;
    apellidos: string;
    cedula: string;
    codigo_qr_skap: string | null;
    centro: string | null;
    area: string | null;
    cargo: string | null;
    is_active: boolean;
    estado_skap: string;
}

interface HistorialRow {
    id: number;
    folio: string;
    fecha_incidente: string | null;
    area: string | null;
    clasificacion: string | null;
    estatus_asignacion: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface HistorialPaginator {
    data: HistorialRow[];
    links: PaginationLink[];
}

export default function ConsultarQrSkap({
    colaboradores,
    colaboradorSeleccionado,
    historial,
}: {
    colaboradores: ColaboradorOption[];
    colaboradorSeleccionado: ColaboradorDetalle | null;
    historial: HistorialPaginator | null;
}) {
    const [selectedId, setSelectedId] = useState(colaboradorSeleccionado ? String(colaboradorSeleccionado.id) : '');

    const seleccionar = (colaborador: ColaboradorOption | null) => {
        setSelectedId(colaborador ? String(colaborador.id) : '');

        if (colaborador) {
            router.get(route('seguridad.acis.consultar-qr'), { colaborador_id: colaborador.id }, { preserveState: true, replace: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Consultar QR SKAP" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <HeadingSmall
                    title="Consultar QR SKAP"
                    description="Busca un colaborador para verificar su código QR SKAP y su historial de reportes ACI."
                />

                <div className="max-w-md">
                    <ColaboradorSearchSelect
                        id="colaborador"
                        label="Colaborador"
                        colaboradores={colaboradores}
                        selectedId={selectedId}
                        onSelect={seleccionar}
                    />
                </div>

                {colaboradorSeleccionado && (
                    <>
                        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                            <CardHeader>
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    {colaboradorSeleccionado.nombres} {colaboradorSeleccionado.apellidos} — {colaboradorSeleccionado.cedula}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-wrap items-center gap-6">
                                <div className="rounded-lg bg-white p-2">
                                    {colaboradorSeleccionado.codigo_qr_skap ? (
                                        <QRCodeSVG value={colaboradorSeleccionado.codigo_qr_skap} size={96} />
                                    ) : (
                                        <div className="flex size-24 items-center justify-center text-xs text-muted-foreground">Sin código</div>
                                    )}
                                </div>
                                <div className="flex flex-col gap-2">
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm text-muted-foreground">
                                            {colaboradorSeleccionado.codigo_qr_skap ?? 'Sin código asignado'}
                                        </span>
                                        <Badge variant={ESTADO_TONOS[colaboradorSeleccionado.estado_skap] ?? 'secondary'}>
                                            {colaboradorSeleccionado.estado_skap}
                                        </Badge>
                                    </div>
                                    <p className="text-sm text-muted-foreground">Centro: {colaboradorSeleccionado.centro ?? '—'}</p>
                                    <p className="text-sm text-muted-foreground">Área: {colaboradorSeleccionado.area ?? '—'}</p>
                                    <p className="text-sm text-muted-foreground">Cargo: {colaboradorSeleccionado.cargo ?? '—'}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <div>
                            <p className="mb-2 text-sm font-medium text-foreground">Historial de reportes ACI como reportante</p>
                            <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Folio</TableHead>
                                            <TableHead>Fecha del incidente</TableHead>
                                            <TableHead>Área</TableHead>
                                            <TableHead>Clasificación</TableHead>
                                            <TableHead>Estatus</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {(!historial || historial.data.length === 0) && (
                                            <TableRow>
                                                <TableCell colSpan={5} className="text-muted-foreground py-6 text-center">
                                                    Sin reportes ACI registrados.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                        {historial?.data.map((fila) => (
                                            <TableRow key={fila.id}>
                                                <TableCell className="font-medium">
                                                    <Link href={route('seguridad.acis.show', fila.id)} className="text-primary hover:underline">
                                                        {fila.folio}
                                                    </Link>
                                                </TableCell>
                                                <TableCell>{fila.fecha_incidente ? new Date(fila.fecha_incidente).toLocaleDateString() : '—'}</TableCell>
                                                <TableCell>{fila.area ?? '—'}</TableCell>
                                                <TableCell>{fila.clasificacion ?? '—'}</TableCell>
                                                <TableCell>{fila.estatus_asignacion ?? '—'}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            {historial && historial.links.length > 3 && (
                                <div className="mt-2 flex flex-wrap gap-1">
                                    {historial.links.map((link, index) => (
                                        <Button
                                            key={index}
                                            variant={link.active ? 'default' : 'outline'}
                                            size="sm"
                                            disabled={!link.url}
                                            asChild={!!link.url}
                                        >
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
                    </>
                )}
            </div>
        </AppLayout>
    );
}
