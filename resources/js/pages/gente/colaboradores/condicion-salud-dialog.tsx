import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/react';
import { HeartPulse } from 'lucide-react';
import { FormEventHandler } from 'react';

const ESTADOS = [
    {
        value: 'Bueno',
        className:
            'border-emerald-300 text-emerald-700 data-[selected=true]:border-emerald-500 data-[selected=true]:bg-emerald-500 data-[selected=true]:text-white dark:border-emerald-500/30 dark:text-emerald-300 dark:data-[selected=true]:border-emerald-500',
    },
    {
        value: 'Regular',
        className:
            'border-amber-300 text-amber-700 data-[selected=true]:border-amber-500 data-[selected=true]:bg-amber-500 data-[selected=true]:text-white dark:border-amber-500/30 dark:text-amber-300 dark:data-[selected=true]:border-amber-500',
    },
    {
        value: 'Malo',
        className:
            'border-red-300 text-red-700 data-[selected=true]:border-red-500 data-[selected=true]:bg-red-500 data-[selected=true]:text-white dark:border-red-500/30 dark:text-red-300 dark:data-[selected=true]:border-red-500',
    },
];

interface CondicionSaludForm {
    colaborador_id: number;
    momento: 'ingreso' | 'salida';
    estado: string;
    observacion: string;
    [key: string]: string | number;
}

export function CondicionSaludDialog({ colaboradorId, trigger }: { colaboradorId: number; trigger: React.ReactNode }) {
    const { data, setData, post, processing, errors, reset } = useForm<CondicionSaludForm>({
        colaborador_id: colaboradorId,
        momento: 'ingreso',
        estado: '',
        observacion: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('seguridad.condiciones-salud.store'), {
            preserveScroll: true,
            onSuccess: () => reset('estado', 'observacion'),
        });
    };

    const requiereObservacion = data.estado === 'Regular' || data.estado === 'Malo';

    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <HeartPulse className="size-5 text-emerald-600 dark:text-emerald-400" />
                        Registrar condición de salud
                    </DialogTitle>
                    <DialogDescription>Selecciona el momento y el estado del colaborador.</DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label>Momento</Label>
                        <div className="flex gap-2">
                            {(['ingreso', 'salida'] as const).map((momento) => (
                                <button
                                    key={momento}
                                    type="button"
                                    data-selected={data.momento === momento}
                                    onClick={() => setData('momento', momento)}
                                    className="flex-1 rounded-md border px-3 py-2 text-sm capitalize transition-colors data-[selected=true]:border-primary data-[selected=true]:bg-primary data-[selected=true]:text-primary-foreground"
                                >
                                    {momento}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label>Estado</Label>
                        <div className="flex gap-2">
                            {ESTADOS.map((estado) => (
                                <button
                                    key={estado.value}
                                    type="button"
                                    data-selected={data.estado === estado.value}
                                    onClick={() => setData('estado', estado.value)}
                                    className={cn('flex-1 rounded-md border px-3 py-2 text-sm font-medium transition-colors', estado.className)}
                                >
                                    {estado.value}
                                </button>
                            ))}
                        </div>
                        <InputError message={errors.estado} />
                    </div>

                    {requiereObservacion && (
                        <div className="grid gap-2">
                            <Label htmlFor="observacion">Observación (obligatoria)</Label>
                            <textarea
                                id="observacion"
                                className="border-input bg-background flex min-h-20 w-full rounded-md border px-3 py-2 text-sm"
                                value={data.observacion}
                                onChange={(e) => setData('observacion', e.target.value)}
                            />
                            <InputError message={errors.observacion} />
                        </div>
                    )}

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing || !data.estado}>
                            Registrar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
