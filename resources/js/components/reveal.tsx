import { useInView } from '@/hooks/use-in-view';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface RevealProps {
    children: ReactNode;
    delay?: number;
    className?: string;
}

export function Reveal({ children, delay = 0, className }: RevealProps) {
    const { ref, isInView } = useInView<HTMLDivElement>();

    return (
        <div
            ref={ref}
            className={cn('transition-all duration-700 ease-out', isInView ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0', className)}
            style={{ transitionDelay: `${delay}ms` }}
        >
            {children}
        </div>
    );
}
