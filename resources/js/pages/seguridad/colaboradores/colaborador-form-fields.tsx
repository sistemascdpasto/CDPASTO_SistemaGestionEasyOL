import { Label } from '@/components/ui/label';
import { type LucideIcon } from 'lucide-react';

export type DocumentInfo = {
    path: string;
    fecha: string | null;
    name?: string;
};

export const ESTADOS_CIVILES = [
    { value: 'soltero', label: 'Soltero(a)' },
    { value: 'union_libre', label: 'Unión libre' },
    { value: 'casado', label: 'Casado(a)' },
    { value: 'divorciado', label: 'Divorciado(a)' },
    { value: 'viudo', label: 'Viudo(a)' },
];

export const CARGOS_ANTERIORES = [
    { value: 'conductor', label: 'Conductor' },
    { value: 'auxiliar_logistico', label: 'Auxiliar logístico' },
    { value: 'supervisor_ruta', label: 'Supervisor de ruta' },
];

export type DocumentGroup =
    | 'Documentos personales'
    | 'Documentos de tránsito'
    | 'Documentos de salud'
    | 'Documentos empresariales'
    | 'Documentos académicos'
    | 'Documentos aprendiz'
    | 'Documentos People'
    | 'Documentos plan padrino'
    | 'Documentos disciplinarios'
    | 'Documentos de aprendizaje';

export const DOCUMENTO_FIELDS: { key: string; label: string; grupo: DocumentGroup }[] = [
    { key: 'documento_cedula', label: 'Documento de cédula', grupo: 'Documentos personales' },
    { key: 'documento_tipo_identificacion', label: 'Archivo del tipo de documento', grupo: 'Documentos personales' },
    { key: 'documento_hoja_vida', label: 'Hoja de vida', grupo: 'Documentos personales' },
    { key: 'documento_libreta_militar', label: 'Libreta militar', grupo: 'Documentos personales' },
    { key: 'documento_antecedentes_procuraduria', label: 'Antecedentes — Procuraduría', grupo: 'Documentos personales' },
    { key: 'documento_antecedentes_contraloria', label: 'Antecedentes — Contraloría', grupo: 'Documentos personales' },
    { key: 'documento_antecedentes_policia', label: 'Antecedentes — Policía Nacional', grupo: 'Documentos personales' },
    { key: 'documento_certificado_bancario', label: 'Certificado bancario', grupo: 'Documentos personales' },
    { key: 'documento_runt', label: 'RUNT', grupo: 'Documentos personales' },

    { key: 'documento_licencia_conduccion', label: 'Licencia de conducción', grupo: 'Documentos de tránsito' },
    { key: 'documento_licencia_a1', label: 'Licencia — A1', grupo: 'Documentos de tránsito' },
    { key: 'documento_licencia_a2', label: 'Licencia — A2', grupo: 'Documentos de tránsito' },
    { key: 'documento_licencia_b1', label: 'Licencia — B1', grupo: 'Documentos de tránsito' },
    { key: 'documento_licencia_b2', label: 'Licencia — B2', grupo: 'Documentos de tránsito' },
    { key: 'documento_licencia_b3', label: 'Licencia — B3', grupo: 'Documentos de tránsito' },
    { key: 'documento_licencia_c1', label: 'Licencia — C1', grupo: 'Documentos de tránsito' },
    { key: 'documento_licencia_c2', label: 'Licencia — C2', grupo: 'Documentos de tránsito' },
    { key: 'documento_licencia_c3', label: 'Licencia — C3', grupo: 'Documentos de tránsito' },
    { key: 'documento_carnet_manejo_defensivo', label: 'Carnet manejo defensivo', grupo: 'Documentos de tránsito' },
    { key: 'documento_certificado_manejo_defensivo', label: 'Certificado manejo defensivo', grupo: 'Documentos de tránsito' },
    { key: 'documento_certificado_carga_pesada', label: 'Certificado conducción de carga pesada', grupo: 'Documentos de tránsito' },
    { key: 'documento_simit', label: 'Documento Simit', grupo: 'Documentos de tránsito' },
    { key: 'documento_recordatorio_vehiculo_licencia_conduccion', label: 'Recordatorio vehículo / licencia', grupo: 'Documentos de tránsito' },

    { key: 'documento_eps', label: 'Documento EPS', grupo: 'Documentos de salud' },
    { key: 'documento_pension', label: 'Documento AFP / pensión', grupo: 'Documentos de salud' },
    { key: 'documento_arl', label: 'Documento ARL', grupo: 'Documentos de salud' },
    { key: 'documento_examen_medico_ocupacional', label: 'Examen médico ocupacional', grupo: 'Documentos de salud' },
    { key: 'documento_certificado_comfamiliar', label: 'Certificado Comfamiliar', grupo: 'Documentos de salud' },
    { key: 'documento_afiliacion_comfamiliar', label: 'Afiliación Comfamiliar', grupo: 'Documentos de salud' },

    { key: 'documento_carnet_ingreso_cd', label: 'Carnet ingreso CD', grupo: 'Documentos empresariales' },
    { key: 'documento_contrato', label: 'Contrato', grupo: 'Documentos empresariales' },
    { key: 'documento_preaviso_terminacion', label: 'Preaviso de terminación', grupo: 'Documentos empresariales' },
    { key: 'documento_prorroga', label: 'Prórroga', grupo: 'Documentos empresariales' },

    { key: 'documento_titulo_bachiller', label: 'Título de bachiller', grupo: 'Documentos académicos' },
    { key: 'documento_titulo_tecnico', label: 'Título técnico', grupo: 'Documentos académicos' },
    { key: 'documento_titulo_tecnologo', label: 'Título tecnólogo', grupo: 'Documentos académicos' },
    { key: 'documento_titulo_profesional', label: 'Título profesional', grupo: 'Documentos académicos' },
    { key: 'documento_titulo_academico', label: 'Título — otro', grupo: 'Documentos académicos' },

    { key: 'documento_sena_carta_presentacion', label: 'Carta de presentación (Aprendiz SENA)', grupo: 'Documentos aprendiz' },

    { key: 'documento_induccion', label: 'Inducción', grupo: 'Documentos People' },
    { key: 'documento_reinduccion', label: 'Reinducción', grupo: 'Documentos People' },
    { key: 'documento_induccion_pilares', label: 'Inducción pilares', grupo: 'Documentos People' },

    { key: 'documento_plan_padrino', label: 'Plan padrino personal nuevo', grupo: 'Documentos plan padrino' },

    { key: 'documento_llamado_atencion', label: 'Llamado de atención', grupo: 'Documentos disciplinarios' },
    { key: 'documento_compromisos', label: 'Compromisos', grupo: 'Documentos disciplinarios' },

    { key: 'documento_escuela_pilotos', label: 'Certificado escuela de pilotos', grupo: 'Documentos de aprendizaje' },
    { key: 'documento_certificado_brigadista', label: 'Certificado de Brigadista', grupo: 'Documentos de aprendizaje' },
];

export const DOCUMENT_GROUPS: DocumentGroup[] = [
    'Documentos personales',
    'Documentos de tránsito',
    'Documentos de salud',
    'Documentos empresariales',
    'Documentos académicos',
    'Documentos aprendiz',
    'Documentos People',
    'Documentos plan padrino',
    'Documentos disciplinarios',
    'Documentos de aprendizaje',
];

export function calcularEdad(fechaNacimiento: string): string {
    if (!fechaNacimiento) return '—';
    const nacimiento = new Date(fechaNacimiento);
    if (Number.isNaN(nacimiento.getTime())) return '—';
    const hoy = new Date();
    let edad = hoy.getFullYear() - nacimiento.getFullYear();
    const aunNoCumple = hoy.getMonth() < nacimiento.getMonth() || (hoy.getMonth() === nacimiento.getMonth() && hoy.getDate() < nacimiento.getDate());
    if (aunNoCumple) edad -= 1;
    return edad >= 0 ? `${edad} años` : '—';
}

/** Tiempo trabajado en la empresa desde la fecha de ingreso (tarjeta con foto, HU06). */
export function calcularTiempoTrabajado(fechaIngreso: string | null): string {
    if (!fechaIngreso) return '—';
    const ingreso = new Date(fechaIngreso);
    if (Number.isNaN(ingreso.getTime())) return '—';

    const hoy = new Date();
    let meses = (hoy.getFullYear() - ingreso.getFullYear()) * 12 + (hoy.getMonth() - ingreso.getMonth());
    if (hoy.getDate() < ingreso.getDate()) meses -= 1;
    if (meses < 0) return '—';

    const anios = Math.floor(meses / 12);
    const mesesRestantes = meses % 12;

    if (anios === 0) return `${mesesRestantes} mes${mesesRestantes === 1 ? '' : 'es'}`;
    if (mesesRestantes === 0) return `${anios} año${anios === 1 ? '' : 's'}`;
    return `${anios} año${anios === 1 ? '' : 's'} ${mesesRestantes} mes${mesesRestantes === 1 ? '' : 'es'}`;
}

export function PillToggle<T extends string>({
    label,
    value,
    options,
    onChange,
    disabled,
}: {
    label: string;
    value: T | '';
    options: { value: T; label: string }[];
    onChange: (value: T) => void;
    disabled?: boolean;
}) {
    return (
        <div className="grid gap-2">
            {label && <Label>{label}</Label>}
            <div className="flex flex-wrap gap-2">
                {options.map((option) => (
                    <button
                        key={option.value}
                        type="button"
                        disabled={disabled}
                        aria-pressed={value === option.value}
                        onClick={() => onChange(option.value)}
                        className={`rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors disabled:opacity-50 ${
                            value === option.value
                                ? 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300'
                                : 'border-input text-muted-foreground hover:border-emerald-200 hover:text-foreground'
                        }`}
                    >
                        {option.label}
                    </button>
                ))}
            </div>
        </div>
    );
}

// Estilos por "tono" reutilizados tanto en el marco de la tarjeta como en el
// círculo del ícono, así una sección se siente coherente de un vistazo.
const TONOS = {
    neutral: {
        card: 'border-border bg-card',
        iconWrap: 'bg-muted text-muted-foreground',
    },
    verde: {
        card: 'border-emerald-100 bg-emerald-50/50 dark:border-emerald-500/15 dark:bg-emerald-500/5',
        iconWrap: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    },
    azul: {
        card: 'border-sky-100 bg-sky-50/50 dark:border-sky-500/15 dark:bg-sky-500/5',
        iconWrap: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
    },
} as const;

export function SeccionCard({
    icon: Icon,
    titulo,
    subtitulo,
    paso,
    children,
    tono = 'neutral',
}: {
    icon?: LucideIcon;
    titulo: string;
    subtitulo?: string;
    paso?: string;
    children: React.ReactNode;
    tono?: keyof typeof TONOS;
}) {
    const estilos = TONOS[tono];

    return (
        <div className={`rounded-2xl border p-4 sm:p-6 ${estilos.card}`}>
            <div className="mb-5 flex items-start justify-between gap-3">
                <div className="flex items-center gap-3">
                    {Icon && (
                        <div className={`flex size-10 shrink-0 items-center justify-center rounded-full ${estilos.iconWrap}`}>
                            <Icon className="size-5" />
                        </div>
                    )}
                    <div>
                        <p className="text-sm font-semibold text-foreground">{titulo}</p>
                        {subtitulo && <p className="text-xs text-muted-foreground">{subtitulo}</p>}
                    </div>
                </div>
                {paso && (
                    <span className="shrink-0 whitespace-nowrap rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                        {paso}
                    </span>
                )}
            </div>
            {children}
        </div>
    );
}
