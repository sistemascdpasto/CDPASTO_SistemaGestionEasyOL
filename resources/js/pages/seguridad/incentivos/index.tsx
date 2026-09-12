import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import AppLayout from '@/layouts/app-layout';
import { ImportarIncentivosDialog } from '@/pages/seguridad/incentivos/importar-dialog';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Seguridad', href: '/modules/seguridad' },
    { title: 'Incentivos', href: '/modules/seguridad/incentivos' },
];

interface ColaboradorResumen {
    id: number;
    nombres: string;
    apellidos: string;
    cedula: string;
}

interface IncentivoRow {
    id: number;
    mes: string | null;
    cedula: string | null;
    nombre: string | null;
    cargo: string | null;
    indicador_1: string | null;
    pilar_1: string | null;
    total_1: string | null;
    meta_1: string | null;
    indicador_2: string | null;
    pilar_2: string | null;
    total_2: string | null;
    meta_2: string | null;
    indicador_3: string | null;
    pilar_3: string | null;
    total_3: string | null;
    meta_3: string | null;
    podium: string | null;
    colaborador: ColaboradorResumen | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface IncentivosPaginator {
    data: IncentivoRow[];
    links: PaginationLink[];
    total: number;
}

interface Filtros {
    mes: string;
    colaborador: string;
}

/** Muestra un valor decimal formateado, o '—' si es nulo/vacío */
function fmtDecimal(valor: string | null | undefined): string {
    if (!valor || valor === '') return '—';
    const n = parseFloat(valor);
    return isNaN(n) ? valor : n.toLocaleString('es-CO', { maximumFractionDigits: 2 });
}

export default function IncentivosIndex({
    incentivos,
    filters,
}: {
    incentivos: IncentivosPaginator;
    filters: Filtros;
}) {
    const [form, setForm] = useState<Filtros>({
        mes: filters.mes ?? '',
        colaborador: filters.colaborador ?? '',
    });

    const isFirst = useRef(true);
    const debouncedForm = useDebouncedValue(form, 400);

    useEffect(() => {
        if (isFirst.current) {
            isFirst.current = false;
            return;
        }
        router.get(route('seguridad.incentivos.index'), debouncedForm, { preserveState: true, replace: true });
    }, [JSON.stringify(debouncedForm)]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Incentivos" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                {/* Encabezado */}
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <HeadingSmall
                        title="Incentivos"
                        description="Registros de indicadores e incentivos importados desde Excel."
                    />
                    <ImportarIncentivosDialog
                        trigger={
                            <Button variant="outline">
                                <Upload />
                                Importar Excel
                            </Button>
                        }
                    />
                </div>

                {/* Filtros */}
                <form className="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <Input
                        placeholder="Mes (ej. Enero, 2026-01…)"
                        value={form.mes}
                        onChange={(e) => setForm({ ...form, mes: e.target.value })}
                    />
                    <Input
                        placeholder="Colaborador (nombre o cédula)"
                        className="md:col-span-2"
                        value={form.colaborador}
                        onChange={(e) => setForm({ ...form, colaborador: e.target.value })}
                    />
                </form>

                {/* Tabla */}
                <div className="overflow-x-auto rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Mes</TableHead>
                                <TableHead>Colaborador</TableHead>
                                <TableHead>Cargo</TableHead>
                                {/* Indicador 1 */}
                                <TableHead>Indicador 1</TableHead>
                                <TableHead>Pilar 1</TableHead>
                                <TableHead className="text-right">Total 1</TableHead>
                                <TableHead className="text-right">Meta 1</TableHead>
                                {/* Indicador 2 */}
                                <TableHead>Indicador 2</TableHead>
                                <TableHead>Pilar 2</TableHead>
                                <TableHead className="text-right">Total 2</TableHead>
                                <TableHead className="text-right">Meta 2</TableHead>
                                {/* Indicador 3 */}
                                <TableHead>Indicador 3</TableHead>
                                <TableHead>Pilar 3</TableHead>
                                <TableHead className="text-right">Total 3</TableHead>
                                <TableHead className="text-right">Meta 3</TableHead>
                                {/* Resultado */}
                                <TableHead>Podium</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {incentivos.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={17}
                                        className="text-muted-foreground py-6 text-center"
                                    >
                                        No hay registros de incentivos que coincidan con los filtros.
                                    </TableCell>
                                </TableRow>
                            )}
                            {incentivos.data.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell>{item.mes ?? '—'}</TableCell>
                                    <TableCell>
                                        {item.colaborador
                                            ? `${item.colaborador.nombres} ${item.colaborador.apellidos}`
                                            : (item.nombre ?? '—')}
                                        <div className="text-muted-foreground text-xs">
                                            {item.cedula ?? item.colaborador?.cedula ?? ''}
                                        </div>
                                    </TableCell>
                                    <TableCell>{item.cargo ?? '—'}</TableCell>
                                    {/* Indicador 1 */}
                                    <TableCell className="max-w-[160px] truncate" title={item.indicador_1 ?? ''}>
                                        {item.indicador_1 ?? '—'}
                                    </TableCell>
                                    <TableCell>{item.pilar_1 ?? '—'}</TableCell>
                                    <TableCell className="text-right">{fmtDecimal(item.total_1)}</TableCell>
                                    <TableCell className="text-right">{fmtDecimal(item.meta_1)}</TableCell>
                                    {/* Indicador 2 */}
                                    <TableCell className="max-w-[160px] truncate" title={item.indicador_2 ?? ''}>
                                        {item.indicador_2 ?? '—'}
                                    </TableCell>
                                    <TableCell>{item.pilar_2 ?? '—'}</TableCell>
                                    <TableCell className="text-right">{fmtDecimal(item.total_2)}</TableCell>
                                    <TableCell className="text-right">{fmtDecimal(item.meta_2)}</TableCell>
                                    {/* Indicador 3 */}
                                    <TableCell className="max-w-[160px] truncate" title={item.indicador_3 ?? ''}>
                                        {item.indicador_3 ?? '—'}
                                    </TableCell>
                                    <TableCell>{item.pilar_3 ?? '—'}</TableCell>
                                    <TableCell className="text-right">{fmtDecimal(item.total_3)}</TableCell>
                                    <TableCell className="text-right">{fmtDecimal(item.meta_3)}</TableCell>
                                    {/* Podium */}
                                    <TableCell>{item.podium ?? '—'}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {/* Paginación */}
                {incentivos.links.length > 3 && (
                    <div className="flex flex-wrap gap-1">
                        {incentivos.links.map((link, index) => (
                            <Button
                                key={index}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                asChild={!!link.url}
                            >
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        preserveScroll
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
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
