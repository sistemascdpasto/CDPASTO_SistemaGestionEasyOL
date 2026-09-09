import { useEffect, useRef, useState } from 'react';

export function useInView<T extends HTMLElement>(options?: IntersectionObserverInit) {
    const ref = useRef<T>(null);
    const [isInView, setIsInView] = useState(false);

    useEffect(() => {
        const node = ref.current;
        if (!node) return;

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setIsInView(true);
                    observer.disconnect();
                }
            },
            { threshold: 0.15, rootMargin: '0px 0px -40px 0px', ...options },
        );

        observer.observe(node);
        return () => observer.disconnect();
    }, [options]);

    return { ref, isInView };
}
