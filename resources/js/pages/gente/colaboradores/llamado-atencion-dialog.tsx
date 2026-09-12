import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { FormEventHandler } from 'react';

interface LlamadoAtencionForm {
    observacion: string;
    documento: File | null;
    [key: string]: string | File | null;
}

export function LlamadoAtencionDialog({ colaboradorId, trigger }: { colaboradorId: number; trigger: React.ReactNode }) {
    const { data, setData, post, processing, errors, reset } = useForm<LlamadoAtencionForm>({
        observacion: '',
        documento: null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('gente.colaboradores.llamados-atencion.store', colaboradorId), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <AlertTriangle className="size-5 text-amber-600 dark:text-amber-400" />
                        Registrar llamado de atención
                    </DialogTitle>
                    <DialogDescription>Describe el motivo y adjunta el soporte si lo tienes.</DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor="observacion">Observación</Label>
                        <Textarea id="observacion" value={data.observacion} onChange={(e) => setData('observacion', e.target.value)} />
                        <InputError message={errors.observacion} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="documento">Documento (opcional)</Label>
                        <input
                            id="documento"
                            type="file"
                            accept=".pdf,.doc,.docx,image/*"
                            className="text-sm"
                            onChange={(e) => setData('documento', e.target.files?.[0] ?? null)}
                        />
                        <InputError message={errors.documento} />
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing || !data.observacion}>
                            Registrar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
