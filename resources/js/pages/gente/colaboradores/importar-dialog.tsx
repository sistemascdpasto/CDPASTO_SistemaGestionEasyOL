import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Upload } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface ImportarForm {
    archivo: File | null;
    [key: string]: File | null;
}

export function ImportarColaboradoresDialog({ trigger }: { trigger: React.ReactNode }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<ImportarForm>({
        archivo: null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('gente.colaboradores.importar'), {
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
                        Importar colaboradores desde Excel
                    </DialogTitle>
                    <DialogDescription>
                        Sube el archivo con el mismo formato de "BASE DE DATOS COLABORADORES" — se procesa la hoja "BASE ACTUALIZADA". Las
                        cédulas que ya existan en el sistema se omiten; el resto se crea activo y con su cuenta de usuario.
                    </DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor="archivo">Archivo Excel (.xlsx)</Label>
                        <input
                            id="archivo"
                            type="file"
                            accept=".xlsx,.xls"
                            className="text-sm"
                            onChange={(e) => setData('archivo', e.target.files?.[0] ?? null)}
                        />
                        <InputError message={errors.archivo} />
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing || !data.archivo}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            Importar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
