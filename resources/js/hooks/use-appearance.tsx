import { useEffect, useState } from 'react';

export type Appearance = 'light' | 'dark' | 'system';

// Dark mode and system-appearance are temporarily disabled — the app is forced to light mode.
// Flip this back to `true` to restore user-selectable dark/system appearance; none of the
// logic below was removed, it's just short-circuited while this is `false`.
export const APPEARANCE_SWITCHING_ENABLED = false;

const prefersDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;

const applyTheme = (appearance: Appearance) => {
    if (!APPEARANCE_SWITCHING_ENABLED) {
        document.documentElement.classList.remove('dark');
        return;
    }

    const isDark = appearance === 'dark' || (appearance === 'system' && prefersDark());

    document.documentElement.classList.toggle('dark', isDark);
};

const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

const handleSystemThemeChange = () => {
    const currentAppearance = localStorage.getItem('appearance') as Appearance;
    applyTheme(currentAppearance || 'system');
};

export function initializeTheme() {
    if (!APPEARANCE_SWITCHING_ENABLED) {
        applyTheme('light');
        return;
    }

    const savedAppearance = (localStorage.getItem('appearance') as Appearance) || 'system';

    applyTheme(savedAppearance);

    // Add the event listener for system theme changes...
    mediaQuery.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    const [appearance, setAppearance] = useState<Appearance>('light');

    const updateAppearance = (mode: Appearance) => {
        if (!APPEARANCE_SWITCHING_ENABLED) {
            return;
        }

        setAppearance(mode);
        localStorage.setItem('appearance', mode);
        applyTheme(mode);
    };

    useEffect(() => {
        if (!APPEARANCE_SWITCHING_ENABLED) {
            return;
        }

        const savedAppearance = localStorage.getItem('appearance') as Appearance | null;
        updateAppearance(savedAppearance || 'system');

        return () => mediaQuery.removeEventListener('change', handleSystemThemeChange);
    }, []);

    return { appearance, updateAppearance };
}
