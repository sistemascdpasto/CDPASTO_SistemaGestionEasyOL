import HeadingSmall from '@/components/heading-small';
import { IconActionButton } from '@/components/icon-action-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, ExternalLink, Pencil, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

interface GlossaryTerm {
    id: number;
    nombre: string;
    definicion: string;
    categoria: string;
    pregunta_numero: string | null;
    source: 'manual' | 'scraped';
    enlaces_de_interes: string | null;
}

interface Paginator {
    data: GlossaryTerm[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Seguridad', href: '/modules/seguridad' },
    { title: 'Glosario', href: '/modules/seguridad/glosario' },
];

export default function GlosarioIndex({
    terms,
    filters,
    categories,
}: {
    terms: Paginator;
    filters: { search: string; categoria: string; orden: string };
    categories: string[];
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const debouncedSearch = useDebouncedValue(search);
    const isFirstRender = useRef(true);

    const applyFilter = useCallback(
        (params: Record<string, string>) => {
            router.get(route('seguridad.glosario.index'), { ...filters, ...params }, { preserveState: true, replace: true });
        },
        [filters],
    );

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        applyFilter({ search: debouncedSearch, page: '1' });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [debouncedSearch]);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilter({ search, page: '1' });
    };

    const handleDelete = (id: number, nombre: string) => {
        if (!window.confirm(`¿Eliminar el término "${nombre}"?`)) return;
        router.delete(route('seguridad.glosario.destroy', id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Glosario" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <HeadingSmall title="Glosario de Términos" description="Términos y definiciones de revisión del camino" />
                    <Button asChild>
                        <Link href={route('seguridad.glosario.create')}>Nuevo Término</Link>
                    </Button>
                </div>

                <form onSubmit={handleSearchSubmit} className="flex gap-2">
                    <Input
                        placeholder="Buscar término o definición..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="max-w-sm"
                    />
                    <Select
                        value={filters.categoria || 'all'}
                        onValueChange={(v) => applyFilter({ categoria: v === 'all' ? '' : v, page: '1' })}
                    >
                        <SelectTrigger className="w-56">
                            <SelectValue placeholder="Todas las categorías" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las categorías</SelectItem>
                            {categories.map((cat) => (
                                <SelectItem key={cat} value={cat}>{cat}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.orden || 'categoria'}
                        onValueChange={(v) => applyFilter({ orden: v === 'categoria' ? '' : v, page: '1' })}
                    >
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="Ordenar por" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="categoria">Categoría (por defecto)</SelectItem>
                            <SelectItem value="nombre_asc">Nombre A-Z</SelectItem>
                            <SelectItem value="nombre_desc">Nombre Z-A</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button type="submit" variant="outline">Buscar</Button>
                </form>

                <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>N°</TableHead>
                                <TableHead>Término</TableHead>
                                <TableHead>Categoría</TableHead>
                                <TableHead className="max-w-xs">Definición</TableHead>
                                <TableHead>Fuente</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {terms.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                        No se encontraron términos.
                                    </TableCell>
                                </TableRow>
                            )}
                            {terms.data.map((term) => (
                                <TableRow key={term.id}>
                                    <TableCell className="text-muted-foreground">{term.pregunta_numero ?? '—'}</TableCell>
                                    <TableCell className="font-medium">{term.nombre}</TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">{term.categoria}</Badge>
                                    </TableCell>
                                    <TableCell className="max-w-xs truncate text-sm text-muted-foreground">
                                        {term.definicion}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={term.source === 'scraped' ? 'default' : 'outline'}>
                                            {term.source === 'scraped' ? 'Web' : 'Manual'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            {term.enlaces_de_interes && (
                                                <Button variant="ghost" size="icon" asChild>
                                                    <a href={term.enlaces_de_interes} target="_blank" rel="noreferrer">
                                                        <ExternalLink className="size-4" />
                                                    </a>
                                                </Button>
                                            )}
                                            <IconActionButton icon={Eye} label="Ver" href={route('seguridad.glosario.show', term.id)} />
                                            <IconActionButton icon={Pencil} label="Editar" href={route('seguridad.glosario.edit', term.id)} />
                                            <IconActionButton
                                                icon={Trash2}
                                                label="Eliminar"
                                                className="text-destructive hover:text-destructive"
                                                onClick={() => handleDelete(term.id, term.nombre)}
                                            />
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {terms.links.length > 3 && (
                    <div className="flex justify-center gap-1">
                        {terms.links.map((link, i) =>
                            link.url ? (
                                <Button key={i} variant={link.active ? 'default' : 'outline'} size="sm" asChild>
                                    <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                </Button>
                            ) : (
                                <Button key={i} variant="outline" size="sm" disabled dangerouslySetInnerHTML={{ __html: link.label }} />
                            ),
                        )}
                    </div>
                )}

                <p className="text-sm text-muted-foreground">
                    {terms.total} término(s) en total{terms.from && terms.to ? `, mostrando ${terms.from}–${terms.to}` : ''}
                </p>
            </div>
        </AppLayout>
    );
}
