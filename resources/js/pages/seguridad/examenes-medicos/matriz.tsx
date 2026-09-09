import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Seguridad', href: '/modules/seguridad' },
    { title: 'Exámenes Médicos', href: '/modules/seguridad/examenes-medicos' },
    { title: 'Matriz Cargo-Examen', href: '/modules/seguridad/examenes-medicos-matriz' },
];

interface ExamenLigero {
    id: number;
    nombre: string;
    categoria: string | null;
}

interface FilaSeleccion {
    obligatorio: boolean;
}

export default function MatrizCargoExamen({
    cargos,
    examenes,
    filtros,
    seleccion,
}: {
    cargos: string[];
    examenes: ExamenLigero[];
    filtros: { cargo: string; tipo_evaluacion: string };
    seleccion: Record<number, FilaSeleccion>;
}) {
    const [aplica, setAplica] = useState<Record<number, boolean>>({});
    const [obligatorio, setObligatorio] = useState<Record<number, boolean>>({});

    useEffect(() => {
        const nuevoAplica: Record<number, boolean> = {};
        const nuevoObligatorio: Record<number, boolean> = {};
        for (const examen of examenes) {
            const fila = seleccion[examen.id];
            nuevoAplica[examen.id] = !!fila;
            nuevoObligatorio[examen.id] = fila?.obligatorio ?? true;
        }
        setAplica(nuevoAplica);
        setObligatorio(nuevoObligatorio);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [filtros.cargo, filtros.tipo_evaluacion]);

    const cambiarFiltro = (cambios: Partial<{ cargo: string; tipo_evaluacion: string }>) => {
        router.get(route('seguridad.examenes-medicos.matriz.index'), { ...filtros, ...cambios }, { preserveState: true, replace: true });
    };

    const [processing, setProcessing] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const examenesSeleccionados = examenes.filter((ex) => aplica[ex.id]).map((ex) => ex.id);
        const obligatorios = examenesSeleccionados.filter((id) => obligatorio[id]);

        router.post(
            route('seguridad.examenes-medicos.matriz.update'),
            { cargo: filtros.cargo, tipo_evaluacion: filtros.tipo_evaluacion, examenes: examenesSeleccionados, obligatorios },
            { preserveScroll: true, onStart: () => setProcessing(true), onFinish: () => setProcessing(false) },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Matriz Cargo-Examen" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <HeadingSmall
                    title="Matriz Cargo-Examen"
                    description="Define qué exámenes le corresponden a cada cargo, según el tipo de evaluación."
                />

                <div className="flex flex-wrap gap-2">
                    <Select value={filtros.cargo || undefined} onValueChange={(v) => cambiarFiltro({ cargo: v })}>
                        <SelectTrigger className="w-72">
                            <SelectValue placeholder="Selecciona un cargo" />
                        </SelectTrigger>
                        <SelectContent>
                            {cargos.map((cargo) => (
                                <SelectItem key={cargo} value={cargo}>
                                    {cargo}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={filtros.tipo_evaluacion} onValueChange={(v) => cambiarFiltro({ tipo_evaluacion: v })}>
                        <SelectTrigger className="w-48">
                            <SelectValue placeholder="Tipo de evaluación" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ingreso">Ingreso</SelectItem>
                            <SelectItem value="periodico">Periódico</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {!filtros.cargo ? (
                    <p className="text-sm text-muted-foreground">Selecciona un cargo para ver y editar su matriz de exámenes.</p>
                ) : (
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Examen</TableHead>
                                        <TableHead>Categoría</TableHead>
                                        <TableHead>Aplica</TableHead>
                                        <TableHead>Obligatorio</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {examenes.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={4} className="text-muted-foreground py-6 text-center">
                                                No hay exámenes activos en el catálogo todavía.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {examenes.map((examen) => (
                                        <TableRow key={examen.id}>
                                            <TableCell className="font-medium">{examen.nombre}</TableCell>
                                            <TableCell>{examen.categoria ?? '—'}</TableCell>
                                            <TableCell>
                                                <input
                                                    type="checkbox"
                                                    className="size-4"
                                                    checked={!!aplica[examen.id]}
                                                    onChange={(e) => setAplica({ ...aplica, [examen.id]: e.target.checked })}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <input
                                                    type="checkbox"
                                                    className="size-4"
                                                    checked={!!obligatorio[examen.id]}
                                                    disabled={!aplica[examen.id]}
                                                    onChange={(e) => setObligatorio({ ...obligatorio, [examen.id]: e.target.checked })}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing}>
                                {processing && <LoaderCircle className="size-4 animate-spin" />}
                                Guardar matriz
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </AppLayout>
    );
}
