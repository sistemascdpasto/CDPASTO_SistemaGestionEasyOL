import { useEffect, useState } from 'react';

/** Retrasa la propagacion de `value` hasta que dejen de llegar cambios durante `delayMs`. */
export function useDebouncedValue<T>(value: T, delayMs = 350): T {
    const [debounced, setDebounced] = useState(value);

    useEffect(() => {
        const timeout = setTimeout(() => setDebounced(value), delayMs);
        return () => clearTimeout(timeout);
    }, [value, delayMs]);

    return debounced;
}
