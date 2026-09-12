import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, ListTodo } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const ESTADOS = ['Pendiente', 'En progreso', 'Completado'];

export function SeguimientoPlanAccionDialog({ planAccionOwdId, trigger }: { planAccionOwdId: number; trigger: React.ReactNode }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        estado: 'En progreso',
        observacion: '',
        fecha: new Date().toISOString().slice(0, 10),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('seguridad.planes-accion-owd.seguimientos.store', planAccionOwdId), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <ListTodo className="size-5 text-emerald-600 dark:text-emerald-400" />
                        Registrar avance del plan de acción
                    </DialogTitle>
                    <DialogDescription>El avance queda en el historial y actualiza el estado vigente del plan.</DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor="estado">Estado</Label>
                        <Select value={data.estado} onValueChange={(v) => setData('estado', v)}>
                            <SelectTrigger id="estado">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {ESTADOS.map((estado) => (
                                    <SelectItem key={estado} value={estado}>
                                        {estado}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.estado} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="fecha">Fecha</Label>
                        <input
                            id="fecha"
                            type="date"
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            value={data.fecha}
                            onChange={(e) => setData('fecha', e.target.value)}
                        />
                        <InputError message={errors.fecha} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="observacion">Observación</Label>
                        <Textarea id="observacion" value={data.observacion} onChange={(e) => setData('observacion', e.target.value)} rows={3} />
                        <InputError message={errors.observacion} />
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            Guardar avance
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
