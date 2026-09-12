import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { ColaboradorOption, ColaboradorSearchSelect } from '@/pages/seguridad/pruebas/colaborador-search-select';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface CreacionFormData {
    colaborador_id: string;
    tipo_evaluacion: 'ingreso' | 'periodico' | 'egreso' | '';
    [key: string]: string;
}

export default function CrearEvaluacion({
    colaboradores,
}: {
    colaboradores: ColaboradorOption[];
}) {
    const [selectedId, setSelectedId] = useState('');
    const { data, setData, post, processing, errors } = useForm<CreacionFormData>({
        colaborador_id: '',
        tipo_evaluacion: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Seguridad', href: '/modules/seguridad' },
        { title: 'Exámenes Médicos', href: '/modules/seguridad/examenes-medicos' },
        { title: 'Nueva evaluación', href: '/modules/seguridad/examenes-medicos/crear' },
    ];

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('seguridad.examenes-medicos.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva evaluación médica" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <HeadingSmall
                    title="Nueva evaluación médica"
                    description="Selecciona el colaborador y el tipo de evaluación — los exámenes requeridos se generan automáticamente según la matriz de su cargo."
                />

                <form onSubmit={submit} className="grid max-w-lg gap-6">
                    <ColaboradorSearchSelect
                        id="colaborador"
                        label="Colaborador"
                        colaboradores={colaboradores}
                        selectedId={selectedId}
                        onSelect={(colaborador) => {
                            setSelectedId(colaborador ? String(colaborador.id) : '');
                            setData('colaborador_id', colaborador ? String(colaborador.id) : '');
                        }}
                        error={errors.colaborador_id}
                        disabled={processing}
                    />

                    <div className="grid gap-2">
                        <Label htmlFor="tipo_evaluacion">Tipo de evaluación</Label>
                        <Select
                            value={data.tipo_evaluacion}
                            onValueChange={(value) => setData('tipo_evaluacion', value as CreacionFormData['tipo_evaluacion'])}
                            disabled={processing}
                        >
                            <SelectTrigger id="tipo_evaluacion">
                                <SelectValue placeholder="Selecciona el tipo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ingreso">Ingreso</SelectItem>
                                <SelectItem value="periodico">Periódico</SelectItem>
                                <SelectItem value="egreso">Egreso</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.tipo_evaluacion} />
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing || !data.colaborador_id || !data.tipo_evaluacion}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            Crear evaluación
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
