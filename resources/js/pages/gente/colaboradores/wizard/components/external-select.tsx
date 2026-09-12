import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useEffect, useId, useState } from 'react';

interface ExternalSelectProps {
    label: string;
    value: string;
    onValueChange: (value: string) => void;
    fetchUrl: string;
    error?: string;
    disabled?: boolean;
    placeholder?: string;
}

/**
 * Campo de texto con autocompletado (datalist nativo) respaldado por un
 * endpoint JSON del backend (api-colombia.com / datos.gov.co ya proxied vía
 * ReferenciaExternaController). Si la integración externa falla, el backend
 * responde con una lista vacía y este campo simplemente queda como texto
 * libre — nunca bloquea el formulario.
 */
export function ExternalSelect({ label, value, onValueChange, fetchUrl, error, disabled, placeholder }: ExternalSelectProps) {
    const listId = useId();
    const [opciones, setOpciones] = useState<string[]>([]);
    const [cargando, setCargando] = useState(true);

    useEffect(() => {
        let cancelado = false;
        setCargando(true);

        fetch(fetchUrl, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((json: { data?: Array<{ nombre: string }> }) => {
                if (!cancelado) setOpciones((json.data ?? []).map((item) => item.nombre));
            })
            .catch(() => {
                if (!cancelado) setOpciones([]);
            })
            .finally(() => {
                if (!cancelado) setCargando(false);
            });

        return () => {
            cancelado = true;
        };
    }, [fetchUrl]);

    return (
        <div className="grid gap-2">
            <Label htmlFor={listId}>{label}</Label>
            <Input
                id={listId}
                list={`${listId}-lista`}
                value={value}
                onChange={(e) => onValueChange(e.target.value)}
                disabled={disabled}
                placeholder={cargando ? 'Cargando opciones...' : (placeholder ?? `Escribe o selecciona ${label.toLowerCase()}`)}
                autoComplete="off"
            />
            <datalist id={`${listId}-lista`}>
                {opciones.map((opcion) => (
                    <option key={opcion} value={opcion} />
                ))}
            </datalist>
            <InputError message={error} />
        </div>
    );
}
