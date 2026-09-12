import { useInView } from '@/hooks/use-in-view';
import { useEffect, useState } from 'react';

interface CountUpProps {
    end: number;
    duration?: number;
    prefix?: string;
    suffix?: string;
    /** Decimales a mostrar (y a animar sin perder precisión). Por defecto 0 (entero). */
    decimals?: number;
}

export function CountUp({ end, duration = 1400, prefix = '', suffix = '', decimals = 0 }: CountUpProps) {
    const { ref, isInView } = useInView<HTMLSpanElement>();
    const [value, setValue] = useState(0);

    useEffect(() => {
        if (!isInView) return;

        const factor = 10 ** decimals;
        let frame: number;
        let start: number | null = null;

        const step = (timestamp: number) => {
            if (start === null) start = timestamp;
            const progress = Math.min((timestamp - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            setValue(Math.round(eased * end * factor) / factor);
            if (progress < 1) frame = requestAnimationFrame(step);
        };

        frame = requestAnimationFrame(step);
        return () => cancelAnimationFrame(frame);
    }, [isInView, end, duration, decimals]);

    return (
        <span ref={ref}>
            {prefix}
            {value.toFixed(decimals)}
            {suffix}
        </span>
    );
}
