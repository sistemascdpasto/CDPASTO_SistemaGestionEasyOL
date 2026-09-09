import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Upload } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface ImportarAcisForm {
    archivos: File[];
    [key: string]: File[];
}

export function ImportarAcisDialog({ trigger }: { trigger: React.ReactNode }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<ImportarAcisForm>({
        archivos: [],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('seguridad.acis.importar'), {
            forceFormData: true,
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
                        <Upload className="size-5 text-emerald-600 dark:text-emerald-400" />
                        Importar reportes ACI desde Excel
                    </DialogTitle>
                    <DialogDescription>
                        Sube uno o varios archivos con el formato "ACIS" (hoja "SIOs"). Cada reporte se relaciona con el colaborador cuyo código QR
                        SKAP coincida con la columna "QR de quien reportó" — si no hay colaborador con ese código, la fila se omite. Los Folios que
                        ya existan se actualizan con la información más reciente del archivo (por ejemplo, si cambió el estatus o se agregó la
                        fecha de cierre).
                    </DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor="archivos">Archivo(s) Excel (.xlsx)</Label>
                        <input
                            id="archivos"
                            type="file"
                            accept=".xlsx,.xls"
                            multiple
                            className="text-sm"
                            onChange={(e) => setData('archivos', Array.from(e.target.files ?? []))}
                        />
                        <InputError message={errors.archivos} />
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing || data.archivos.length === 0}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            Importar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
