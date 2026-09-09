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
    { title: 'Conceptos de aptitud', href: '/modules/seguridad/examenes-medicos-conceptos' },
];

interface Concepto {
    id: number;
    nombre: string;
    activo: boolean;
}

interface FormData {
    nombre: string;
    activo: boolean;
    [key: string]: string | boolean;
}

function ConceptoDialog({ concepto }: { concepto?: Concepto }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, patch, processing, errors, reset } = useForm<FormData>({
        nombre: concepto?.nombre ?? '',
        activo: concepto?.activo ?? true,
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

        if (concepto) {
            patch(route('seguridad.examenes-medicos.conceptos.update', concepto.id), opciones);
        } else {
            post(route('seguridad.examenes-medicos.conceptos.store'), opciones);
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {concepto ? (
                    <Button type="button" size="icon" variant="ghost">
                        <Pencil className="size-4" />
                    </Button>
                ) : (
                    <Button type="button">
                        <Plus />
                        Nuevo concepto
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{concepto ? 'Editar concepto de aptitud' : 'Nuevo concepto de aptitud'}</DialogTitle>
                    <DialogDescription>Catálogo de conceptos de aptitud disponibles para las evaluaciones médicas.</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="nombre">Nombre</Label>
                        <Input id="nombre" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} disabled={processing} />
                        <InputError message={errors.nombre} />
                    </div>
                    {concepto && (
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

export default function ConceptosAptitud({ conceptos }: { conceptos: Concepto[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Conceptos de aptitud" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <HeadingSmall title="Conceptos de aptitud" description="Catálogo de resultados posibles de una evaluación médica." />
                    <ConceptoDialog />
                </div>

                <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {conceptos.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={3} className="text-muted-foreground py-6 text-center">
                                        No hay conceptos de aptitud registrados todavía.
                                    </TableCell>
                                </TableRow>
                            )}
                            {conceptos.map((concepto) => (
                                <TableRow key={concepto.id}>
                                    <TableCell className="font-medium">{concepto.nombre}</TableCell>
                                    <TableCell>
                                        <Badge variant={concepto.activo ? 'default' : 'secondary'}>{concepto.activo ? 'Activo' : 'Inactivo'}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end">
                                            <ConceptoDialog concepto={concepto} />
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
