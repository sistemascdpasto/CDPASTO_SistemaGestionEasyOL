import InputError from '@/components/input-error';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SeccionCard } from '@/pages/seguridad/colaboradores/colaborador-form-fields';
import { CalendarClock, Camera, Cpu, Download, FileSpreadsheet, FileText, Gauge, Paperclip, X } from 'lucide-react';
import { useRef, useState } from 'react';

export interface DispositivoFormData {
    codigo: string;
    marca: string;
    modelo: string;
    fecha_calibracion: string;
    fecha_vencimiento_certificado: string;
    documento: File | null;
    valor_min: string;
    valor_max: string;
    estado: string;
    imagenes: File[];
    deleted_imagenes_indices: number[];
    documentos: File[];
    deleted_documentos_indices: number[];
    [key: string]: string | File | File[] | number[] | null;
}

export interface SavedDocumento {
    path: string;
    nombre: string;
}

const ESTADOS = ['Disponible', 'En uso', 'En mantenimiento', 'Fuera de servicio'];

function esPdf(path: string): boolean {
    return path.toLowerCase().endsWith('.pdf');
}

interface DispositivoFormFieldsProps {
    data: DispositivoFormData;
    setData: <K extends keyof DispositivoFormData>(key: K, value: DispositivoFormData[K]) => void;
    errors: Partial<Record<keyof DispositivoFormData, string>>;
    processing: boolean;
    savedImagenes?: string[];
    savedDocumentos?: SavedDocumento[];
    /** Documento legado de una sola columna, previo a soportar varios documentos. No es borrable individualmente. */
    documentoLegado?: string | null;
}

export function DispositivoFormFields({
    data,
    setData,
    errors,
    processing,
    savedImagenes = [],
    savedDocumentos = [],
    documentoLegado,
}: DispositivoFormFieldsProps) {
    const imagenesInputRef = useRef<HTMLInputElement>(null);
    const documentosInputRef = useRef<HTMLInputElement>(null);
    const [filesImagenes, setFilesImagenes] = useState<{ file: File; preview: string }[]>([]);
    const [deletedIndices, setDeletedIndices] = useState<number[]>([]);
    const [filesDocumentos, setFilesDocumentos] = useState<{ file: File; preview: string }[]>([]);
    const [deletedDocumentosIndices, setDeletedDocumentosIndices] = useState<number[]>([]);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [pendingDeleteIndex, setPendingDeleteIndex] = useState<number | null>(null);
    const [deleteType, setDeleteType] = useState<'imagen' | 'documento' | null>(null);
    const [preview, setPreview] = useState<{ url: string; label: string; esPdf: boolean } | null>(null);

    const handleImagenesChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const newFiles = Array.from(e.target.files ?? []).map((file) => ({ file, preview: URL.createObjectURL(file) }));
        const updated = [...filesImagenes, ...newFiles];
        setFilesImagenes(updated);
        setData(
            'imagenes',
            updated.map((f) => f.file),
        );
        if (imagenesInputRef.current) imagenesInputRef.current.value = '';
    };

    const removeImagen = (index: number) => {
        const updated = filesImagenes.filter((_, i) => i !== index);
        setFilesImagenes(updated);
        setData(
            'imagenes',
            updated.map((f) => f.file),
        );
    };

    const removeSavedImagen = (index: number) => {
        if (deletedIndices.includes(index)) {
            const updated = deletedIndices.filter((i) => i !== index);
            setDeletedIndices(updated);
            setData('deleted_imagenes_indices', updated);
        } else {
            setPendingDeleteIndex(index);
            setDeleteType('imagen');
            setShowDeleteDialog(true);
        }
    };

    const handleDocumentosChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const newFiles = Array.from(e.target.files ?? []).map((file) => ({ file, preview: URL.createObjectURL(file) }));
        const updated = [...filesDocumentos, ...newFiles];
        setFilesDocumentos(updated);
        setData(
            'documentos',
            updated.map((f) => f.file),
        );
        if (documentosInputRef.current) documentosInputRef.current.value = '';
    };

    const removeDocumento = (index: number) => {
        const updated = filesDocumentos.filter((_, i) => i !== index);
        setFilesDocumentos(updated);
        setData(
            'documentos',
            updated.map((f) => f.file),
        );
    };

    const removeSavedDocumento = (index: number) => {
        if (deletedDocumentosIndices.includes(index)) {
            const updated = deletedDocumentosIndices.filter((i) => i !== index);
            setDeletedDocumentosIndices(updated);
            setData('deleted_documentos_indices', updated);
        } else {
            setPendingDeleteIndex(index);
            setDeleteType('documento');
            setShowDeleteDialog(true);
        }
    };

    const confirmDelete = () => {
        if (pendingDeleteIndex !== null) {
            if (deleteType === 'imagen') {
                const updated = [...deletedIndices, pendingDeleteIndex];
                setDeletedIndices(updated);
                setData('deleted_imagenes_indices', updated);
            } else if (deleteType === 'documento') {
                const updated = [...deletedDocumentosIndices, pendingDeleteIndex];
                setDeletedDocumentosIndices(updated);
                setData('deleted_documentos_indices', updated);
            }
        }
        setShowDeleteDialog(false);
        setPendingDeleteIndex(null);
        setDeleteType(null);
    };

    return (
        <div className="grid gap-6">
            <SeccionCard icon={Cpu} titulo="Información del dispositivo" tono="verde">
                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="grid gap-2">
                        <Label htmlFor="codigo">Código / Serial</Label>
                        <Input id="codigo" value={data.codigo} onChange={(e) => setData('codigo', e.target.value)} disabled={processing} required autoFocus />
                        <InputError message={errors.codigo} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="marca">Marca</Label>
                        <Input id="marca" value={data.marca} onChange={(e) => setData('marca', e.target.value)} disabled={processing} />
                        <InputError message={errors.marca} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="modelo">Modelo</Label>
                        <Input id="modelo" value={data.modelo} onChange={(e) => setData('modelo', e.target.value)} disabled={processing} />
                        <InputError message={errors.modelo} />
                    </div>
                </div>
            </SeccionCard>

            <SeccionCard icon={CalendarClock} titulo="Calibración" tono="azul">
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="fecha_calibracion">Fecha de calibración</Label>
                        <Input
                            id="fecha_calibracion"
                            type="date"
                            value={data.fecha_calibracion}
                            onChange={(e) => setData('fecha_calibracion', e.target.value)}
                            disabled={processing}
                        />
                        <InputError message={errors.fecha_calibracion} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="fecha_vencimiento_certificado">Vencimiento del certificado</Label>
                        <Input
                            id="fecha_vencimiento_certificado"
                            type="date"
                            value={data.fecha_vencimiento_certificado}
                            onChange={(e) => setData('fecha_vencimiento_certificado', e.target.value)}
                            disabled={processing}
                        />
                        <InputError message={errors.fecha_vencimiento_certificado} />
                    </div>
                </div>
            </SeccionCard>

            <SeccionCard icon={Gauge} titulo="Rango de valores y estado" tono="verde">
                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="grid gap-2">
                        <Label htmlFor="valor_min">Valor mínimo válido</Label>
                        <Input
                            id="valor_min"
                            type="number"
                            step="0.001"
                            value={data.valor_min}
                            onChange={(e) => setData('valor_min', e.target.value)}
                            disabled={processing}
                            required
                        />
                        <InputError message={errors.valor_min} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="valor_max">Valor máximo válido</Label>
                        <Input
                            id="valor_max"
                            type="number"
                            step="0.001"
                            value={data.valor_max}
                            onChange={(e) => setData('valor_max', e.target.value)}
                            disabled={processing}
                            required
                        />
                        <InputError message={errors.valor_max} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="estado">Estado</Label>
                        <Select value={data.estado} onValueChange={(value) => setData('estado', value)} disabled={processing}>
                            <SelectTrigger id="estado">
                                <SelectValue placeholder="Selecciona un estado" />
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
                </div>
            </SeccionCard>

            <SeccionCard icon={Paperclip} titulo="Documentos" subtitulo="Certificados, fichas técnicas, etc." tono="azul">
                {documentoLegado && (
                    <div className="mb-4 grid gap-2">
                        <Label>Documento original</Label>
                        <button
                            type="button"
                            onClick={() => setPreview({ url: documentoLegado, label: 'Documento original', esPdf: esPdf(documentoLegado) })}
                            className="inline-flex w-fit items-center gap-1.5 rounded-md border border-border px-3 py-2 text-sm text-primary underline underline-offset-4"
                        >
                            <FileText className="size-4" />
                            Ver documento original
                        </button>
                    </div>
                )}
                <input
                    ref={documentosInputRef}
                    id="documentos"
                    type="file"
                    accept=".pdf,.xls,.xlsx,.doc,.docx"
                    multiple
                    className="hidden"
                    onChange={handleDocumentosChange}
                />
                <InputError message={errors.documentos} />
                <div className="grid gap-2 sm:grid-cols-2 md:grid-cols-3">
                    {savedDocumentos.map((documento, index) => {
                        const eliminado = deletedDocumentosIndices.includes(index);
                        return (
                            <div
                                key={`saved-doc-${index}`}
                                className={`flex items-center gap-2 rounded-lg border p-2 transition-opacity ${eliminado ? 'opacity-50' : 'border-emerald-300 dark:border-emerald-500/30'}`}
                            >
                                <button
                                    type="button"
                                    onClick={() => !eliminado && setPreview({ url: documento.path, label: documento.nombre, esPdf: esPdf(documento.path) })}
                                    className="flex min-w-0 flex-1 items-center gap-2 text-left text-sm"
                                    disabled={eliminado}
                                >
                                    {esPdf(documento.path) ? (
                                        <FileText className="size-4 shrink-0 text-muted-foreground" />
                                    ) : (
                                        <FileSpreadsheet className="size-4 shrink-0 text-muted-foreground" />
                                    )}
                                    <span className="truncate">{documento.nombre}</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => removeSavedDocumento(index)}
                                    className={`flex size-5 shrink-0 items-center justify-center rounded-full text-white shadow transition-colors ${
                                        eliminado ? 'bg-muted-foreground' : 'bg-red-500 hover:bg-red-600'
                                    }`}
                                >
                                    <X className="size-3" />
                                </button>
                            </div>
                        );
                    })}
                    {filesDocumentos.map((item, index) => (
                        <div key={`new-doc-${index}`} className="flex items-center gap-2 rounded-lg border border-sky-300 p-2 dark:border-sky-500/40">
                            <button
                                type="button"
                                onClick={() => setPreview({ url: item.preview, label: item.file.name, esPdf: esPdf(item.file.name) })}
                                className="flex min-w-0 flex-1 items-center gap-2 text-left text-sm"
                            >
                                {esPdf(item.file.name) ? (
                                    <FileText className="size-4 shrink-0 text-muted-foreground" />
                                ) : (
                                    <FileSpreadsheet className="size-4 shrink-0 text-muted-foreground" />
                                )}
                                <span className="truncate">{item.file.name}</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => removeDocumento(index)}
                                className="flex size-5 shrink-0 items-center justify-center rounded-full bg-red-500 text-white shadow hover:bg-red-600"
                            >
                                <X className="size-3" />
                            </button>
                        </div>
                    ))}
                    <button
                        type="button"
                        onClick={() => documentosInputRef.current?.click()}
                        className="flex h-full min-h-11 w-full flex-col items-center justify-center rounded-lg border-2 border-dashed border-border text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                    >
                        <span className="text-sm">+ Agregar documento</span>
                    </button>
                </div>
            </SeccionCard>

            <SeccionCard icon={Camera} titulo="Imágenes del dispositivo" subtitulo="Opcional" tono="verde">
                <input ref={imagenesInputRef} id="imagenes" type="file" accept="image/*" multiple className="hidden" onChange={handleImagenesChange} />
                <InputError message={errors.imagenes} />
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                    {savedImagenes.map((path, index) => (
                        <div
                            key={`saved-${index}`}
                            className={`group relative cursor-pointer transition-opacity ${deletedIndices.includes(index) ? 'opacity-50' : ''}`}
                        >
                            <img
                                src={path}
                                alt={`Imagen guardada ${index + 1}`}
                                className="h-24 w-full rounded-lg border-2 border-emerald-300 object-cover transition-transform group-hover:scale-105 dark:border-emerald-500/40"
                            />
                            <div className="absolute left-1 top-1 rounded-full bg-emerald-600 px-2 py-1 text-xs font-semibold text-white dark:bg-emerald-500">
                                Guardada
                            </div>
                            <button
                                type="button"
                                onClick={() => removeSavedImagen(index)}
                                className={`absolute right-1 top-1 flex size-5 items-center justify-center rounded-full shadow transition-colors ${
                                    deletedIndices.includes(index) ? 'bg-muted-foreground text-white' : 'bg-red-500 text-white hover:bg-red-600'
                                }`}
                            >
                                <X className="h-3 w-3" />
                            </button>
                        </div>
                    ))}
                    {filesImagenes.map((item, index) => (
                        <div key={`new-${index}`} className="group relative cursor-pointer">
                            <img
                                src={item.preview}
                                alt={`Nueva imagen ${index + 1}`}
                                className="h-24 w-full rounded-lg border-2 border-sky-300 object-cover transition-transform group-hover:scale-105 dark:border-sky-500/40"
                            />
                            <div className="absolute left-1 top-1 rounded-full bg-sky-600 px-2 py-1 text-xs font-semibold text-white dark:bg-sky-500">Nueva</div>
                            <button
                                type="button"
                                onClick={() => removeImagen(index)}
                                className="absolute right-1 top-1 flex size-5 items-center justify-center rounded-full bg-red-500 text-white shadow transition-colors hover:bg-red-600"
                            >
                                <X className="h-3 w-3" />
                            </button>
                        </div>
                    ))}
                    <button
                        type="button"
                        onClick={() => imagenesInputRef.current?.click()}
                        className="flex h-24 w-full flex-col items-center justify-center rounded-lg border-2 border-dashed border-border text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                    >
                        <span className="text-2xl font-light leading-none">+</span>
                        <span className="mt-1 text-xs">Agregar</span>
                    </button>
                </div>
            </SeccionCard>

            <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                <AlertDialogContent className="border-red-200 bg-red-50 dark:border-red-500/20 dark:bg-red-950">
                    <AlertDialogTitle className="text-red-900 dark:text-red-200">
                        {deleteType === 'imagen' ? 'Eliminar imagen' : 'Eliminar documento'}
                    </AlertDialogTitle>
                    <AlertDialogDescription className="text-red-800 dark:text-red-300">
                        ¿Estás seguro de que deseas eliminar {deleteType === 'imagen' ? 'esta imagen' : 'este documento'} del dispositivo? Esta acción no
                        se puede deshacer.
                    </AlertDialogDescription>
                    <div className="flex justify-end gap-3">
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction onClick={confirmDelete} className="bg-red-600 text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600">
                            Eliminar
                        </AlertDialogAction>
                    </div>
                </AlertDialogContent>
            </AlertDialog>

            <Dialog open={preview !== null} onOpenChange={(open) => !open && setPreview(null)}>
                <DialogContent className="max-h-[90vh] max-w-3xl">
                    <DialogTitle className="flex items-center justify-between gap-4 pr-6">
                        <span className="truncate">{preview?.label}</span>
                        {preview && (
                            <a
                                href={preview.url}
                                target="_blank"
                                rel="noreferrer"
                                download
                                className="inline-flex items-center gap-1.5 rounded-md border border-input px-3 py-1.5 text-sm hover:bg-accent"
                            >
                                <Download className="size-4" />
                                Descargar
                            </a>
                        )}
                    </DialogTitle>
                    {preview?.esPdf ? (
                        <iframe src={preview.url} title={preview.label} className="h-[75vh] w-full rounded-md border border-border" />
                    ) : (
                        <div className="flex h-40 flex-col items-center justify-center gap-2 rounded-md border border-dashed border-border text-sm text-muted-foreground">
                            <FileSpreadsheet className="size-8" />
                            Este tipo de archivo no se puede previsualizar. Descárgalo para abrirlo.
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
}
