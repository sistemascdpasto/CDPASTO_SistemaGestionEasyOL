import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Pencil, Plus } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Seguridad', href: '/modules/seguridad' },
    { title: 'Exámenes Médicos', href: '/modules/seguridad/examenes-medicos' },
    { title: 'Catálogo de recomendaciones', href: '/modules/seguridad/examenes-medicos-recomendaciones' },
];

interface Recomendacion {
    id: number;
    nombre: string;
    categoria: string;
    activo: boolean;
}

interface FormData {
    nombre: string;
    categoria: string;
    activo: boolean;
    [key: string]: string | boolean;
}

function RecomendacionDialog({ recomendacion, categorias }: { recomendacion?: Recomendacion; categorias: Record<string, string> }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, patch, processing, errors, reset } = useForm<FormData>({
        nombre: recomendacion?.nombre ?? '',
        categoria: recomendacion?.categoria ?? '',
        activo: recomendacion?.activo ?? true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const opciones = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        };

        if (recomendacion) {
            patch(route('seguridad.examenes-medicos.recomendaciones.catalogo.update', recomendacion.id), opciones);
        } else {
            post(route('seguridad.examenes-medicos.recomendaciones.catalogo.store'), opciones);
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {recomendacion ? (
                    <Button type="button" size="icon" variant="ghost">
                        <Pencil className="size-4" />
                    </Button>
                ) : (
                    <Button type="button">
                        <Plus />
                        Nueva recomendación
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{recomendacion ? 'Editar recomendación' : 'Nueva recomendación'}</DialogTitle>
                    <DialogDescription>Catálogo de recomendaciones que puede generar una evaluación médica (HU-054).</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="nombre">Nombre</Label>
                        <Input id="nombre" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} disabled={processing} />
                        <InputError message={errors.nombre} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="categoria">Categoría</Label>
                        <Select value={data.categoria} onValueChange={(value) => setData('categoria', value)} disabled={processing}>
                            <SelectTrigger id="categoria">
                                <SelectValue placeholder="Selecciona la categoría" />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(categorias).map(([valor, etiqueta]) => (
                                    <SelectItem key={valor} value={valor}>
                                        {etiqueta}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.categoria} />
                    </div>
                    {recomendacion && (
                        <div className="flex items-center gap-2">
                            <input
                                id="activo"
                                type="checkbox"
                                checked={data.activo}
                                onChange={(e) => setData('activo', e.target.checked)}
                                disabled={processing}
                                className="size-4"
                            />
                            <Label htmlFor="activo" className="font-normal">
                                Activo
                            </Label>
                        </div>
                    )}
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing || !data.nombre || !data.categoria}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            Guardar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function RecomendacionesCatalogo({
    recomendaciones,
    categorias,
}: {
    recomendaciones: Recomendacion[];
    categorias: Record<string, string>;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Catálogo de recomendaciones" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <HeadingSmall
                        title="Catálogo de recomendaciones"
                        description="Recomendaciones médicas, ocupacionales y de hábitos que se pueden asignar a una evaluación."
                    />
                    <RecomendacionDialog categorias={categorias} />
                </div>

                <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Categoría</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {recomendaciones.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="text-muted-foreground py-6 text-center">
                                        No hay recomendaciones registradas todavía.
                                    </TableCell>
                                </TableRow>
                            )}
                            {recomendaciones.map((recomendacion) => (
                                <TableRow key={recomendacion.id}>
                                    <TableCell className="font-medium">{recomendacion.nombre}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline">{categorias[recomendacion.categoria] ?? recomendacion.categoria}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={recomendacion.activo ? 'default' : 'secondary'}>
                                            {recomendacion.activo ? 'Activo' : 'Inactivo'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end">
                                            <RecomendacionDialog recomendacion={recomendacion} categorias={categorias} />
                                        </div>
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
