import AnimatedContent from '@/components/AnimatedContent';
import { CountUp } from '@/components/count-up';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

interface KpiCardProps {
    label: string;
    /** Círculo de color con ícono en el header. Se omite si no se pasa. */
    icon?: LucideIcon;
    color?: string;
    /**
     * Número -> se anima con CountUp (prefix/suffix/decimals). String -> se
     * muestra tal cual, sin animar (para valores compuestos como "33% (5/15)").
     */
    value: number | string;
    decimals?: number;
    prefix?: string;
    suffix?: string;
    /** Línea secundaria opcional debajo del valor (ej. desglose por rol). */
    secondaryText?: string;
}

/**
 * Tarjeta de indicador compartida por los distintos dashboards de la app
 * (Varadas, Asistencia GeoVictoria, Consultas SIMIT, indicadores de
 * Seguridad, panel de Administrador...). Antes cada página repetía este
 * mismo bloque de Card a mano.
 */
export function KpiCard({ label, icon: Icon, color = '#0065B9', value, decimals, prefix, suffix, secondaryText }: KpiCardProps) {
    return (
        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
            <CardHeader className={cn('space-y-0 pb-2', Icon && 'flex flex-row items-center justify-between')}>
                <CardTitle className="text-sm font-medium text-muted-foreground">{label}</CardTitle>
                {Icon && (
                    <div className="flex size-9 items-center justify-center rounded-full" style={{ backgroundColor: `${color}1a`, color }}>
                        <Icon className="size-4" />
                    </div>
                )}
            </CardHeader>
            <CardContent>
                <p className="text-2xl font-semibold tracking-tight">
                    {typeof value === 'number' ? (
                        <CountUp end={value} decimals={decimals} prefix={prefix} suffix={suffix} />
                    ) : (
                        value
                    )}
                </p>
                {secondaryText && <p className="mt-1 text-xs text-muted-foreground">{secondaryText}</p>}
            </CardContent>
        </Card>
    );
}

/**
 * Envuelve una grilla de KpiCard con la animación de entrada de React Bits
 * (AnimatedContent, ver components.json -> registro @react-bits). Una sola
 * animación para toda la grilla, no por tarjeta.
 */
export function KpiCardGrid({ children, className }: { children: ReactNode; className?: string }) {
    return (
        <AnimatedContent distance={16} duration={0.5}>
            <div className={cn('grid gap-4', className)}>{children}</div>
        </AnimatedContent>
    );
}
