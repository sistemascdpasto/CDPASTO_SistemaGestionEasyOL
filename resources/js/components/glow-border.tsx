import { cn } from '@/lib/utils';
import { type CSSProperties, type ReactNode } from 'react';

interface GlowBorderProps {
    children: ReactNode;
    className?: string;
    colors?: [string, string, string];
    /** Rounded corner class applied to both the glow and the content mask. */
    rounded?: string;
}

/**
 * Wraps content with a slowly-rotating conic-gradient ring, masked to only
 * show as a thin border — a dependency-free take on react-bits' animated
 * gradient border / "Glare Card" edge glow.
 */
export function GlowBorder({ children, className, colors = ['#0065B9', '#FDC30D', '#D4102A'], rounded = 'rounded-2xl' }: GlowBorderProps) {
    const [a, b, c] = colors;

    return (
        <div className={cn('relative', rounded, className)}>
            <div
                className={cn('animate-spin-slow pointer-events-none absolute -inset-[1px] opacity-70 blur-[1px]', rounded)}
                style={
                    {
                        backgroundImage: `conic-gradient(from 0deg, ${a}, ${b}, ${c}, ${a})`,
                    } as CSSProperties
                }
            />
            <div className={cn('relative', rounded)}>{children}</div>
        </div>
    );
}
