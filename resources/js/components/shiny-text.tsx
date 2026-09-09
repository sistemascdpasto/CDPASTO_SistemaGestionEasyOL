import { cn } from '@/lib/utils';
import { type CSSProperties, type ReactNode } from 'react';

interface ShinyTextProps {
    children: ReactNode;
    className?: string;
    /** Base color the shine sweeps across. Defaults to the Easy Logística green. */
    color?: string;
}

/** A text sweep-shine effect, inspired by react-bits' "Shiny Text". */
export function ShinyText({ children, className, color }: ShinyTextProps) {
    return (
        <span
            className={cn('shiny-text inline-block animate-shine', className)}
            style={color ? ({ '--shiny-base': color } as CSSProperties) : undefined}
        >
            {children}
        </span>
    );
}
