import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

interface GlossaryTerm {
    id: number;
    nombre: string;
    definicion: string;
    categoria: string;
    pregunta_numero: string | null;
    representacion: string | null;
    enlaces_de_interes: string | null;
}

export default function GlosarioForm({ term, categories }: { term?: GlossaryTerm; categories: string[] }) {
    const isEdit = !!term;

    const { data, setData, post, patch, processing, errors } = useForm({
        nombre: term?.nombre ?? '',
        definicion: term?.definicion ?? '',
        categoria: term?.categoria ?? '',
        pregunta_numero: term?.pregunta_numero ?? '',
        representacion: term?.representacion ?? '',
        enlaces_de_interes: term?.enlaces_de_interes ?? '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Seguridad', href: '/modules/seguridad' },
        { title: 'Glosario', href: '/modules/seguridad/glosario' },
        { title: isEdit ? 'Editar Término' : 'Nuevo Término', href: '#' },
    ];

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            patch(route('seguridad.glosario.update', term!.id));
        } else {
            post(route('seguridad.glosario.store'));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEdit ? 'Editar Término' : 'Nuevo Término'} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div>
                    <h1 className="text-xl font-semibold">{isEdit ? 'Editar Término' : 'Nuevo Término'}</h1>
                    <p className="text-sm text-muted-foreground">Complete la información del término del glosario.</p>
                </div>

                <form onSubmit={submit} className="grid max-w-2xl gap-5">
                    <div className="grid gap-2">
                        <Label htmlFor="nombre">Término *</Label>
                        <Input id="nombre" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} />
                        {errors.nombre && <p className="text-sm text-destructive">{errors.nombre}</p>}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="categoria">Categoría *</Label>
                        <Select value={data.categoria} onValueChange={(v) => setData('categoria', v)}>
                            <SelectTrigger id="categoria">
                                <SelectValue placeholder="Seleccionar categoría" />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((cat) => (
                                    <SelectItem key={cat} value={cat}>{cat}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.categoria && <p className="text-sm text-destructive">{errors.categoria}</p>}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="pregunta_numero">Número de Pregunta</Label>
                        <Input
                            id="pregunta_numero"
                            placeholder="ej: 2.1"
                            value={data.pregunta_numero}
                            onChange={(e) => setData('pregunta_numero', e.target.value)}
                        />
                        {errors.pregunta_numero && <p className="text-sm text-destructive">{errors.pregunta_numero}</p>}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="definicion">Definición *</Label>
                        <Textarea
                            id="definicion"
                            rows={4}
                            value={data.definicion}
                            onChange={(e) => setData('definicion', e.target.value)}
                        />
                        {errors.definicion && <p className="text-sm text-destructive">{errors.definicion}</p>}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="representacion">Representación</Label>
                        <Textarea
                            id="representacion"
                            rows={3}
                            value={data.representacion}
                            onChange={(e) => setData('representacion', e.target.value)}
                        />
                        {errors.representacion && <p className="text-sm text-destructive">{errors.representacion}</p>}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="enlaces_de_interes">Enlace de Interés</Label>
                        <Input
                            id="enlaces_de_interes"
                            type="url"
                            placeholder="https://..."
                            value={data.enlaces_de_interes}
                            onChange={(e) => setData('enlaces_de_interes', e.target.value)}
                        />
                        {errors.enlaces_de_interes && <p className="text-sm text-destructive">{errors.enlaces_de_interes}</p>}
                    </div>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {isEdit ? 'Guardar Cambios' : 'Crear Término'}
                        </Button>
                        <Button type="button" variant="outline" onClick={() => window.history.back()}>
                            Cancelar
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
