import { cn } from '@/lib/utils';
import { useRef, type MouseEvent, type ReactNode } from 'react';

interface SpotlightCardProps {
    children: ReactNode;
    className?: string;
    /** Accent color used for the cursor-tracked glow. */
    color?: string;
}

/**
 * Wraps content in a container that reveals a soft radial glow following the
 * cursor, plus a matching border tint on hover — a lightweight, dependency-free
 * take on the react-bits "Spotlight Card" effect.
 */
export function SpotlightCard({ children, className, color = '#0065B9' }: SpotlightCardProps) {
    const ref = useRef<HTMLDivElement>(null);

    const handleMouseMove = (event: MouseEvent<HTMLDivElement>) => {
        const el = ref.current;
        if (!el) return;
        const rect = el.getBoundingClientRect();
        el.style.setProperty('--spot-x', `${event.clientX - rect.left}px`);
        el.style.setProperty('--spot-y', `${event.clientY - rect.top}px`);
    };

    return (
        <div
            ref={ref}
            onMouseMove={handleMouseMove}
            className={cn('group/spotlight relative isolate overflow-hidden', className)}
            style={{ '--spot-color': color } as React.CSSProperties}
        >
            <div className="spotlight-surface pointer-events-none absolute inset-0 z-0 opacity-0 transition-opacity duration-500 group-hover/spotlight:opacity-100" />
            <div className="relative z-10 flex h-full flex-col">{children}</div>
        </div>
    );
}
