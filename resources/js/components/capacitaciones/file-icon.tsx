import {
    Clapperboard,
    ExternalLink,
    File,
    FileSpreadsheet,
    FileText,
    Presentation,
} from 'lucide-react';

export function getFileCategoryInfo(tipo: string | null | undefined, mime?: string | null) {
    const t = (tipo || '').toLowerCase();
    const m = (mime || '').toLowerCase();

    if (t === 'video' || m.includes('video')) {
        return {
            label: 'Video',
            icon: Clapperboard,
            bgColor: 'bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400',
            borderColor: 'border-rose-200 dark:border-rose-900/50',
        };
    }
    if (t === 'presentacion' || m.includes('presentation') || m.includes('powerpoint')) {
        return {
            label: 'PowerPoint',
            icon: Presentation,
            bgColor: 'bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400',
            borderColor: 'border-amber-200 dark:border-amber-900/50',
        };
    }
    if (t === 'hoja_calculo' || m.includes('sheet') || m.includes('excel') || m.includes('csv')) {
        return {
            label: 'Excel',
            icon: FileSpreadsheet,
            bgColor: 'bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400',
            borderColor: 'border-emerald-200 dark:border-emerald-900/50',
        };
    }
    if (t === 'pdf' || m.includes('pdf')) {
        return {
            label: 'PDF',
            icon: FileText,
            bgColor: 'bg-red-500/10 text-red-600 dark:bg-red-500/20 dark:text-red-400',
            borderColor: 'border-red-200 dark:border-red-900/50',
        };
    }
    if (t === 'enlace') {
        return {
            label: 'Enlace Web',
            icon: ExternalLink,
            bgColor: 'bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400',
            borderColor: 'border-sky-200 dark:border-sky-900/50',
        };
    }
    return {
        label: 'Documento',
        icon: File,
        bgColor: 'bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400',
        borderColor: 'border-blue-200 dark:border-blue-900/50',
    };
}

export function FileIcon({ tipo, mime, className = 'size-5' }: { tipo?: string | null; mime?: string | null; className?: string }) {
    const info = getFileCategoryInfo(tipo, mime);
    const IconComp = info.icon;
    return <IconComp className={className} />;
}
