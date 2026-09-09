import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    { title: 'Catálogo de exámenes', href: '/modules/seguridad/examenes-medicos-catalogo' },
];

interface Examen {
    id: number;
    nombre: string;
    categoria: string | null;
    tipo_examen: string | null;
    activo: boolean;
}

interface FormData {
    nombre: string;
    categoria: string;
    tipo_examen: string;
    activo: boolean;
    [key: string]: string | boolean;
}

function ExamenDialog({ examen }: { examen?: Examen }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, patch, processing, errors, reset } = useForm<FormData>({
        nombre: examen?.nombre ?? '',
        categoria: examen?.categoria ?? '',
        tipo_examen: examen?.tipo_examen ?? '',
        activo: examen?.activo ?? true,
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

        if (examen) {
            patch(route('seguridad.examenes-medicos.catalogo.update', examen.id), opciones);
        } else {
            post(route('seguridad.examenes-medicos.catalogo.store'), opciones);
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {examen ? (
                    <Button type="button" size="icon" variant="ghost">
                        <Pencil className="size-4" />
                    </Button>
                ) : (
                    <Button type="button">
                        <Plus />
                        Nuevo examen
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{examen ? 'Editar examen' : 'Nuevo examen'}</DialogTitle>
                    <DialogDescription>Catálogo de exámenes disponibles para la matriz Cargo-Examen.</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="nombre">Nombre</Label>
                        <Input id="nombre" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} disabled={processing} />
                        <InputError message={errors.nombre} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="categoria">Categoría</Label>
                        <Input id="categoria" value={data.categoria} onChange={(e) => setData('categoria', e.target.value)} disabled={processing} />
                        <InputError message={errors.categoria} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="tipo_examen">Tipo (Médico / Paraclínico)</Label>
                        <Input
                            id="tipo_examen"
                            value={data.tipo_examen}
                            onChange={(e) => setData('tipo_examen', e.target.value)}
                            disabled={processing}
                        />
                        <InputError message={errors.tipo_examen} />
                    </div>
                    {examen && (
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
                        <Button type="submit" disabled={processing || !data.nombre}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            Guardar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function CatalogoExamenes({ examenes }: { examenes: Examen[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Catálogo de exámenes" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <HeadingSmall title="Catálogo de exámenes" description="Exámenes médicos disponibles para armar la matriz por cargo." />
                    <ExamenDialog />
                </div>

                <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Categoría</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {examenes.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-muted-foreground py-6 text-center">
                                        No hay exámenes registrados todavía.
                                    </TableCell>
                                </TableRow>
                            )}
                            {examenes.map((examen) => (
                                <TableRow key={examen.id}>
                                    <TableCell className="font-medium">{examen.nombre}</TableCell>
                                    <TableCell>{examen.categoria ?? '—'}</TableCell>
                                    <TableCell>{examen.tipo_examen ?? '—'}</TableCell>
                                    <TableCell>
                                        <Badge variant={examen.activo ? 'default' : 'secondary'}>{examen.activo ? 'Activo' : 'Inactivo'}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end">
                                            <ExamenDialog examen={examen} />
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
