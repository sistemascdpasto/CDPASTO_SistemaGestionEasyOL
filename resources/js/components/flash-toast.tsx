import { type FlashStatus, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, X, XCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

type ToastType = 'success' | 'warning' | 'error';

const TONOS: Record<ToastType, { icon: typeof CheckCircle2; classes: string; iconClasses: string }> = {
    success: {
        icon: CheckCircle2,
        classes: 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200',
        iconClasses: 'text-emerald-600 dark:text-emerald-400',
    },
    warning: {
        icon: AlertTriangle,
        classes: 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200',
        iconClasses: 'text-amber-600 dark:text-amber-400',
    },
    error: {
        icon: XCircle,
        classes: 'border-red-200 bg-red-50 text-red-900 dark:border-red-500/30 dark:bg-red-950 dark:text-red-200',
        iconClasses: 'text-red-600 dark:text-red-400',
    },
};

function normalizar(status: FlashStatus | undefined): { message: string; type: ToastType } | null {
    if (!status) return null;
    if (typeof status === 'string') return { message: status, type: 'success' };
    return { message: status.message, type: status.type ?? 'success' };
}

/**
 * Muestra `->with('status', ...)` de los controladores como un aviso
 * flotante que se cierra solo. Acepta tanto el string simple que ya mandan
 * varios controladores como el objeto {message, type} nuevo (importación
 * masiva de colaboradores).
 */
export function FlashToast() {
    const { status } = usePage<SharedData>().props;
    const [toast, setToast] = useState<{ message: string; type: ToastType } | null>(null);

    useEffect(() => {
        const siguiente = normalizar(status);
        if (siguiente) setToast(siguiente);
    }, [status]);

    useEffect(() => {
        if (!toast) return;
        const timer = setTimeout(() => setToast(null), 5000);
        return () => clearTimeout(timer);
    }, [toast]);

    if (!toast) return null;

    const { icon: Icon, classes, iconClasses } = TONOS[toast.type];

    return (
        <div className="fixed right-4 top-4 z-50 w-full max-w-sm animate-in fade-in slide-in-from-top-2">
            <div className={`flex items-start gap-3 rounded-xl border p-4 shadow-lg ${classes}`}>
                <Icon className={`mt-0.5 size-5 shrink-0 ${iconClasses}`} />
                <p className="flex-1 text-sm font-medium">{toast.message}</p>
                <button type="button" onClick={() => setToast(null)} aria-label="Cerrar aviso" className="shrink-0 opacity-70 hover:opacity-100">
                    <X className="size-4" />
                </button>
            </div>
        </div>
    );
}
