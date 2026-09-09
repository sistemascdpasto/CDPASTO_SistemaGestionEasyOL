import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { DispositivoFormData, DispositivoFormFields, type SavedDocumento } from '@/pages/seguridad/dispositivos/dispositivo-form-fields';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

interface EditableDispositivo {
    id: number;
    codigo: string;
    marca: string | null;
    modelo: string | null;
    fecha_calibracion: string | null;
    fecha_vencimiento_certificado: string | null;
    valor_min: string;
    valor_max: string;
    estado: string;
    imagenes_paths?: string[];
    documentos_paths?: SavedDocumento[];
    documento_path: string | null;
}

export default function EditDispositivo({ dispositivo }: { dispositivo: EditableDispositivo }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Seguridad', href: '/modules/seguridad' },
        { title: 'Dispositivos', href: '/modules/seguridad/dispositivos' },
        { title: dispositivo.codigo, href: `/modules/seguridad/dispositivos/${dispositivo.id}/edit` },
    ];

    const { data, setData, post, processing, errors, transform } = useForm<DispositivoFormData>({
        codigo: dispositivo.codigo,
        marca: dispositivo.marca ?? '',
        modelo: dispositivo.modelo ?? '',
        fecha_calibracion: dispositivo.fecha_calibracion ?? '',
        fecha_vencimiento_certificado: dispositivo.fecha_vencimiento_certificado ?? '',
        documento: null,
        valor_min: dispositivo.valor_min,
        valor_max: dispositivo.valor_max,
        estado: dispositivo.estado,
        imagenes: [],
        deleted_imagenes_indices: [],
        documentos: [],
        deleted_documentos_indices: [],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        // Laravel no soporta multipart en PUT, así que se envía por POST con _method spoof.
        transform((data) => ({ ...data, _method: 'put' }));
        post(route('seguridad.dispositivos.update', dispositivo.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Editar dispositivo" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <HeadingSmall title="Editar dispositivo" description="Actualiza la información técnica del alcoholímetro." />

                <form onSubmit={submit} className="grid gap-6">
                    <DispositivoFormFields
                        data={data}
                        setData={setData}
                        errors={errors}
                        processing={processing}
                        savedImagenes={dispositivo.imagenes_paths ?? []}
                        savedDocumentos={dispositivo.documentos_paths ?? []}
                        documentoLegado={dispositivo.documento_path ? `/storage/${dispositivo.documento_path}` : null}
                    />

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            Guardar cambios
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
