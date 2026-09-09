import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ImageIcon, LoaderCircle, Pencil, Plus, Trash2, CheckCircle2, XCircle } from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Seguridad', href: '/modules/seguridad' },
    { title: 'Encuestas de Morbilidad', href: '/modules/seguridad/encuestas-morbilidad' },
    { title: 'Catálogo de Preguntas', href: '/modules/seguridad/encuestas-morbilidad-preguntas' },
];

interface PreguntaItem {
    id: number;
    numero_pregunta: number;
    seccion_numero: number;
    seccion_titulo: string;
    texto: string;
    tipo: string;
    obligatorio: boolean;
    opciones: string[] | null;
    con_otro: boolean;
    segmento: string | null;
    orden: number;
    activo: boolean;
}

interface TipoValido {
    value: string;
    label: string;
}

export default function EncuestasMorbilidadPreguntasIndex({
    preguntas,
    tiposValidos,
}: {
    preguntas: PreguntaItem[];
    tiposValidos: TipoValido[];
}) {
    const [busqueda, setBusqueda] = useState('');
    const [modalCrearAbierto, setModalCrearAbierto] = useState(false);
    const [preguntaAEditar, setPreguntaAEditar] = useState<PreguntaItem | null>(null);
    const [preguntaAEliminar, setPreguntaAEliminar] = useState<PreguntaItem | null>(null);

    // Formulario para Crear / Editar
    const form = useForm<{
        seccion_numero: number;
        seccion_titulo: string;
        texto: string;
        tipo: string;
        obligatorio: boolean;
        opciones: string[];
        con_otro: boolean;
        segmento: string;
        activo: boolean;
    }>({
        seccion_numero: 1,
        seccion_titulo: 'Condiciones de Salud y Características Físicas',
        texto: '',
        tipo: 'si_no',
        obligatorio: true,
        opciones: [] as string[],
        con_otro: false,
        segmento: '',
        activo: true,
    });

    const [nuevaOpcion, setNuevaOpcion] = useState('');

    const abrirCrear = () => {
        form.reset();
        form.setData({
            seccion_numero: 1,
            seccion_titulo: 'Condiciones de Salud y Características Físicas',
            texto: '',
            tipo: 'si_no',
            obligatorio: true,
            opciones: [],
            con_otro: false,
            segmento: '',
            activo: true,
        });
        setModalCrearAbierto(true);
    };

    const abrirEditar = (p: PreguntaItem) => {
        setPreguntaAEditar(p);
        form.setData({
            seccion_numero: p.seccion_numero,
            seccion_titulo: p.seccion_titulo,
            texto: p.texto,
            tipo: p.tipo,
            obligatorio: p.obligatorio,
            opciones: p.opciones ?? [],
            con_otro: p.con_otro,
            segmento: p.segmento ?? '',
            activo: p.activo,
        });
    };

    const guardarCrear = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('seguridad.encuestas-morbilidad.preguntas.store'), {
            onSuccess: () => {
                setModalCrearAbierto(false);
                form.reset();
            },
        });
    };

    const guardarEditar = (e: React.FormEvent) => {
        e.preventDefault();
        if (!preguntaAEditar) return;
        form.patch(route('seguridad.encuestas-morbilidad.preguntas.update', preguntaAEditar.id), {
            onSuccess: () => {
                setPreguntaAEditar(null);
                form.reset();
            },
        });
    };

    const confirmarEliminar = () => {
        if (!preguntaAEliminar) return;
        router.delete(route('seguridad.encuestas-morbilidad.preguntas.destroy', preguntaAEliminar.id), {
            onSuccess: () => setPreguntaAEliminar(null),
        });
    };

    const agregarOpcion = () => {
        if (!nuevaOpcion.trim()) return;
        form.setData('opciones', [...form.data.opciones, nuevaOpcion.trim()]);
        setNuevaOpcion('');
    };

    const quitarOpcion = (idx: number) => {
        form.setData('opciones', form.data.opciones.filter((_, i) => i !== idx));
    };

    const preguntasFiltradas = useMemo(() => {
        if (!busqueda.trim()) return preguntas;
        const q = busqueda.toLowerCase();
        return preguntas.filter(
            p =>
                p.texto.toLowerCase().includes(q) ||
                p.seccion_titulo.toLowerCase().includes(q) ||
                String(p.seccion_numero).includes(q)
        );
    }, [preguntas, busqueda]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Catálogo de Preguntas — Morbilidad Sentida" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <HeadingSmall
                        title="Catálogo de Preguntas de Morbilidad Sentida"
                        description="Agrega, edita o elimina las preguntas activas del formulario de salud de los colaboradores."
                    />
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild className="gap-1.5">
                            <Link href={route('seguridad.encuestas-morbilidad.secciones.index')}>
                                <ImageIcon className="size-4" />
                                Portadas de secciones
                            </Link>
                        </Button>
                        <Button onClick={abrirCrear} className="gap-1.5">
                            <Plus className="size-4" />
                            Nueva Pregunta
                        </Button>
                    </div>
                </div>

                <div className="flex gap-4">
                    <Input
                        placeholder="Buscar por pregunta, sección..."
                        value={busqueda}
                        onChange={e => setBusqueda(e.target.value)}
                        className="max-w-sm"
                    />
                </div>

                <div className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-16">Sección</TableHead>
                                <TableHead className="w-16"># Pregunta</TableHead>
                                <TableHead>Pregunta / Enunciado</TableHead>
                                <TableHead>Tipo de Respuesta</TableHead>
                                <TableHead className="w-24">Obligatoria</TableHead>
                                <TableHead className="w-20">Estado</TableHead>
                                <TableHead className="text-right w-24">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {preguntasFiltradas.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="py-6 text-center text-muted-foreground">
                                        No hay preguntas registradas que coincidan con la búsqueda.
                                    </TableCell>
                                </TableRow>
                            )}
                            {preguntasFiltradas.map(p => (
                                <TableRow key={p.id}>
                                    <TableCell className="font-semibold text-xs">
                                        Sección {p.seccion_numero}
                                    </TableCell>
                                    <TableCell className="text-xs font-mono text-muted-foreground">
                                        #{p.numero_pregunta}
                                    </TableCell>
                                    <TableCell className="max-w-md text-xs">
                                        <p className="font-medium text-foreground">{p.texto}</p>
                                        <p className="text-[10px] text-muted-foreground">{p.seccion_titulo}</p>
                                    </TableCell>
                                    <TableCell className="text-xs">
                                        <Badge variant="outline" className="text-[10px]">
                                            {tiposValidos.find(t => t.value === p.tipo)?.label ?? p.tipo}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-xs">
                                        {p.obligatorio ? (
                                            <Badge variant="default" className="text-[10px]">Sí</Badge>
                                        ) : (
                                            <Badge variant="secondary" className="text-[10px]">No</Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-xs">
                                        {p.activo ? (
                                            <span className="flex items-center gap-1 text-emerald-600 text-[11px]">
                                                <CheckCircle2 className="size-3" /> Activo
                                            </span>
                                        ) : (
                                            <span className="flex items-center gap-1 text-muted-foreground text-[11px]">
                                                <XCircle className="size-3" /> Inactivo
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            <Button variant="ghost" size="icon" className="size-7" onClick={() => abrirEditar(p)}>
                                                <Pencil className="size-3.5" />
                                            </Button>
                                            <Button variant="ghost" size="icon" className="size-7 text-red-500 hover:text-red-600 hover:bg-red-50" onClick={() => setPreguntaAEliminar(p)}>
                                                <Trash2 className="size-3.5" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {/* Modal Crear */}
                <Dialog open={modalCrearAbierto} onOpenChange={setModalCrearAbierto}>
                    <DialogContent className="max-w-lg">
                        <DialogHeader>
                            <DialogTitle>Nueva Pregunta de Morbilidad</DialogTitle>
                            <DialogDescription>
                                Agrega una nueva pregunta al formulario dinámico de salud.
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={guardarCrear} className="space-y-4 pt-2">
                            <div className="grid grid-cols-3 gap-3">
                                <div>
                                    <Label className="text-xs"># Sección</Label>
                                    <Input
                                        type="number"
                                        min={1}
                                        max={50}
                                        value={form.data.seccion_numero}
                                        onChange={e => form.setData('seccion_numero', Number(e.target.value))}
                                    />
                                </div>
                                <div className="col-span-2">
                                    <Label className="text-xs">Título de Sección</Label>
                                    <Input
                                        value={form.data.seccion_titulo}
                                        onChange={e => form.setData('seccion_titulo', e.target.value)}
                                        placeholder="Ej. Condiciones de Salud"
                                    />
                                </div>
                            </div>

                            <div>
                                <Label className="text-xs">Texto / Enunciado de la Pregunta</Label>
                                <textarea
                                    className="min-h-16 w-full rounded-md border border-input bg-background px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                                    value={form.data.texto}
                                    onChange={e => form.setData('texto', e.target.value)}
                                    placeholder="Ingresa la pregunta..."
                                    required
                                />
                            </div>

                            <div>
                                <Label className="text-xs">Tipo de Campo / Respuesta</Label>
                                <select
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                                    value={form.data.tipo}
                                    onChange={e => form.setData('tipo', e.target.value)}
                                >
                                    {tiposValidos.map(t => (
                                        <option key={t.value} value={t.value}>{t.label}</option>
                                    ))}
                                </select>
                            </div>

                            {(form.data.tipo === 'checkbox_multiple' || form.data.tipo === 'actividades_salud') && (
                                <div className="space-y-2 rounded-md border border-border p-3">
                                    <Label className="text-xs font-semibold">Opciones configurables</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            className="h-8 text-xs"
                                            placeholder="Nueva opción..."
                                            value={nuevaOpcion}
                                            onChange={e => setNuevaOpcion(e.target.value)}
                                        />
                                        <Button type="button" size="sm" onClick={agregarOpcion}>Agregar</Button>
                                    </div>
                                    <ul className="space-y-1">
                                        {form.data.opciones.map((op, idx) => (
                                            <li key={idx} className="flex items-center justify-between text-xs rounded bg-muted px-2 py-1">
                                                <span>{op}</span>
                                                <button type="button" onClick={() => quitarOpcion(idx)} className="text-red-500 hover:underline text-[10px]">Quitar</button>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="obligatorio"
                                    checked={form.data.obligatorio}
                                    onChange={e => form.setData('obligatorio', e.target.checked)}
                                    className="accent-primary"
                                />
                                <Label htmlFor="obligatorio" className="text-xs cursor-pointer">Respuesta obligatoria</Label>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setModalCrearAbierto(false)}>Cancelar</Button>
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing && <LoaderCircle className="size-4 animate-spin" />}
                                    Guardar Pregunta
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Modal Editar */}
                <Dialog open={!!preguntaAEditar} onOpenChange={() => setPreguntaAEditar(null)}>
                    <DialogContent className="max-w-lg">
                        <DialogHeader>
                            <DialogTitle>Editar Pregunta #{preguntaAEditar?.numero_pregunta}</DialogTitle>
                            <DialogDescription>
                                Modifica la configuración de la pregunta seleccionada.
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={guardarEditar} className="space-y-4 pt-2">
                            <div className="grid grid-cols-3 gap-3">
                                <div>
                                    <Label className="text-xs"># Sección</Label>
                                    <Input
                                        type="number"
                                        min={1}
                                        max={50}
                                        value={form.data.seccion_numero}
                                        onChange={e => form.setData('seccion_numero', Number(e.target.value))}
                                    />
                                </div>
                                <div className="col-span-2">
                                    <Label className="text-xs">Título de Sección</Label>
                                    <Input
                                        value={form.data.seccion_titulo}
                                        onChange={e => form.setData('seccion_titulo', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div>
                                <Label className="text-xs">Texto / Enunciado</Label>
                                <textarea
                                    className="min-h-16 w-full rounded-md border border-input bg-background px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                                    value={form.data.texto}
                                    onChange={e => form.setData('texto', e.target.value)}
                                    required
                                />
                            </div>

                            <div>
                                <Label className="text-xs">Tipo de Campo</Label>
                                <select
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                                    value={form.data.tipo}
                                    onChange={e => form.setData('tipo', e.target.value)}
                                >
                                    {tiposValidos.map(t => (
                                        <option key={t.value} value={t.value}>{t.label}</option>
                                    ))}
                                </select>
                            </div>

                            {(form.data.tipo === 'checkbox_multiple' || form.data.tipo === 'actividades_salud') && (
                                <div className="space-y-2 rounded-md border border-border p-3">
                                    <Label className="text-xs font-semibold font-sans">Opciones configurables</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            className="h-8 text-xs"
                                            placeholder="Nueva opción..."
                                            value={nuevaOpcion}
                                            onChange={e => setNuevaOpcion(e.target.value)}
                                        />
                                        <Button type="button" size="sm" onClick={agregarOpcion}>Agregar</Button>
                                    </div>
                                    <ul className="space-y-1">
                                        {form.data.opciones.map((op, idx) => (
                                            <li key={idx} className="flex items-center justify-between text-xs rounded bg-muted px-2 py-1">
                                                <span>{op}</span>
                                                <button type="button" onClick={() => quitarOpcion(idx)} className="text-red-500 hover:underline text-[10px]">Quitar</button>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            <div className="flex items-center gap-4">
                                <div className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="obligatorio-edit"
                                        checked={form.data.obligatorio}
                                        onChange={e => form.setData('obligatorio', e.target.checked)}
                                        className="accent-primary"
                                    />
                                    <Label htmlFor="obligatorio-edit" className="text-xs cursor-pointer">Obligatoria</Label>
                                </div>

                                <div className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="activo-edit"
                                        checked={form.data.activo}
                                        onChange={e => form.setData('activo', e.target.checked)}
                                        className="accent-primary"
                                    />
                                    <Label htmlFor="activo-edit" className="text-xs cursor-pointer">Activa</Label>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setPreguntaAEditar(null)}>Cancelar</Button>
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing && <LoaderCircle className="size-4 animate-spin" />}
                                    Actualizar
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Modal Eliminar */}
                <Dialog open={!!preguntaAEliminar} onOpenChange={() => setPreguntaAEliminar(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>¿Eliminar pregunta del catálogo?</DialogTitle>
                            <DialogDescription>
                                Estás a punto de eliminar la pregunta #{preguntaAEliminar?.numero_pregunta}: "{preguntaAEliminar?.texto}".
                                Esta acción eliminará la pregunta del catálogo.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setPreguntaAEliminar(null)}>Cancelar</Button>
                            <Button variant="destructive" onClick={confirmarEliminar}>
                                Confirmar eliminación
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
