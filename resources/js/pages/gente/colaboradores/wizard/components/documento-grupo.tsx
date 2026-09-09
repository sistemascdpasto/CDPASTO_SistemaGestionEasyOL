import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { type DocumentInfo } from '@/pages/seguridad/colaboradores/colaborador-form-fields';
import { AlertCircle, ChevronDown, FolderOpen } from 'lucide-react';
import { useEffect, useState } from 'react';
import { errorDeArchivo } from '../utils';
import { SimpleFileField } from './simple-file-field';

export type DocumentoGrupoItem = { key: string; label: string };

interface DocumentoGrupoProps {
    titulo: string;
    numero: string;
    items: DocumentoGrupoItem[];
    files: Record<string, File[]>;
    existingDocumentos?: Record<string, DocumentInfo[]>;
    onChange: (key: string, files: File[]) => void;
    errors?: Record<string, string | undefined>;
    disabled?: boolean;
    defaultOpen?: boolean;
}

/**
 * Sección colapsable de un grupo de documentos del Paso 4 (4.1 a 4.10 del
 * PDF), con un SimpleFileField por tipo de documento. Si el servidor rechazó
 * algún archivo del grupo (formato o tamaño inválido), el grupo se abre solo
 * y marca el ícono en rojo — si no, un grupo colapsado podía esconder el
 * único error del formulario y el usuario nunca se enteraba de por qué
 * "Finalizar registro" no avanzaba.
 */
export function DocumentoGrupo({ titulo, numero, items, files, existingDocumentos, onChange, errors = {}, disabled, defaultOpen }: DocumentoGrupoProps) {
    const tieneError = items.some((item) => errorDeArchivo(errors, item.key));
    const [open, setOpen] = useState((defaultOpen ?? false) || tieneError);

    useEffect(() => {
        if (tieneError) setOpen(true);
    }, [tieneError]);

    if (items.length === 0) return null;

    return (
        <Collapsible open={open} onOpenChange={setOpen} className={`rounded-xl border ${tieneError ? 'border-red-300 dark:border-red-500/40' : 'border-border'}`}>
            <CollapsibleTrigger className="flex w-full items-center justify-between gap-2 px-4 py-3 text-left">
                <span className="flex items-center gap-2 text-sm font-semibold text-foreground">
                    {tieneError ? <AlertCircle className="size-4 text-red-600 dark:text-red-400" /> : <FolderOpen className="size-4 text-muted-foreground" />}
                    {numero} {titulo}
                </span>
                <ChevronDown className={`size-4 text-muted-foreground transition-transform ${open ? 'rotate-180' : ''}`} />
            </CollapsibleTrigger>
            <CollapsibleContent className="grid gap-4 border-t border-border px-4 py-4">
                {items.map((item) => (
                    <SimpleFileField
                        key={item.key}
                        label={item.label}
                        files={files[item.key] ?? []}
                        existing={existingDocumentos?.[item.key]}
                        onChange={(newFiles) => onChange(item.key, newFiles)}
                        error={errorDeArchivo(errors, item.key)}
                        disabled={disabled}
                    />
                ))}
            </CollapsibleContent>
        </Collapsible>
    );
}
