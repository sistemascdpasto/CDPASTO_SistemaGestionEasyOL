import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/react';
import { GraduationCap } from 'lucide-react';
import { FormEventHandler } from 'react';

const NUEVO = '__nuevo__';

interface EntrenamientoForm {
    entrenamiento_id: string;
    entrenamiento_nombre: string;
    fecha_registro: string;
    hora_registro: string;
    [key: string]: string;
}

export function EntrenamientoDialog({
    colaboradorId,
    catalogo,
    trigger,
}: {
    colaboradorId: number;
    catalogo: { id: number; nombre: string }[];
    trigger: React.ReactNode;
}) {
    const { data, setData, post, processing, errors, reset, transform } = useForm<EntrenamientoForm>({
        entrenamiento_id: '',
        entrenamiento_nombre: '',
        fecha_registro: new Date().toISOString().slice(0, 10),
        hora_registro: new Date().toTimeString().slice(0, 5),
    });

    const esNuevo = data.entrenamiento_id === NUEVO;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        transform((formData) => ({ ...formData, entrenamiento_id: esNuevo ? '' : formData.entrenamiento_id }));
        post(route('gente.colaboradores.entrenamientos.store', colaboradorId), {
            preserveScroll: true,
            onSuccess: () => reset('entrenamiento_id', 'entrenamiento_nombre'),
        });
    };

    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <GraduationCap className="size-5 text-emerald-600 dark:text-emerald-400" />
                        Registrar entrenamiento
                    </DialogTitle>
                    <DialogDescription>Selecciona un entrenamiento existente o crea uno nuevo.</DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor="entrenamiento_id">Entrenamiento</Label>
                        <Select value={data.entrenamiento_id} onValueChange={(value) => setData('entrenamiento_id', value)}>
                            <SelectTrigger id="entrenamiento_id">
                                <SelectValue placeholder="Selecciona un entrenamiento" />
                            </SelectTrigger>
                            <SelectContent>
                                {catalogo.map((entrenamiento) => (
                                    <SelectItem key={entrenamiento.id} value={String(entrenamiento.id)}>
                                        {entrenamiento.nombre}
                                    </SelectItem>
                                ))}
                                <SelectItem value={NUEVO}>+ Crear nuevo entrenamiento</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.entrenamiento_id} />
                    </div>

                    {esNuevo && (
                        <div className="grid gap-2 border-l-2 border-emerald-300 pl-3 dark:border-emerald-500/30">
                            <Label htmlFor="entrenamiento_nombre">Nombre del nuevo entrenamiento</Label>
                            <Input id="entrenamiento_nombre" value={data.entrenamiento_nombre} onChange={(e) => setData('entrenamiento_nombre', e.target.value)} />
                            <InputError message={errors.entrenamiento_nombre} />
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="fecha_registro">Fecha</Label>
                            <Input id="fecha_registro" type="date" value={data.fecha_registro} onChange={(e) => setData('fecha_registro', e.target.value)} />
                            <InputError message={errors.fecha_registro} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="hora_registro">Hora</Label>
                            <Input id="hora_registro" type="time" value={data.hora_registro} onChange={(e) => setData('hora_registro', e.target.value)} />
                            <InputError message={errors.hora_registro} />
                        </div>
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing || !data.entrenamiento_id || (esNuevo && !data.entrenamiento_nombre)}>
                            Registrar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
