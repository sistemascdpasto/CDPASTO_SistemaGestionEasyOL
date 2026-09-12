import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Search, User } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export interface ColaboradorOption {
    id: number;
    nombres: string;
    apellidos: string;
    cedula: string;
    turno: string | null;
    cargo: string | null;
}

const TURNO_LABELS: Record<string, string> = { manana: 'Mañana', tarde: 'Tarde', noche: 'Noche' };

function etiqueta(colaborador: ColaboradorOption): string {
    return `${colaborador.nombres} ${colaborador.apellidos} — ${colaborador.cedula}`;
}

interface ColaboradorSearchSelectProps {
    id: string;
    label: string;
    colaboradores: ColaboradorOption[];
    selectedId: string;
    onSelect: (colaborador: ColaboradorOption | null) => void;
    error?: string;
    disabled?: boolean;
}

/**
 * Campo único de búsqueda + selección: mientras se escribe un nombre o
 * número de identificación, la lista de colaboradores se filtra en vivo
 * debajo del campo (en vez de un buscador separado por encima de un select).
 */
export function ColaboradorSearchSelect({ id, label, colaboradores, selectedId, onSelect, error, disabled }: ColaboradorSearchSelectProps) {
    const seleccionado = colaboradores.find((c) => String(c.id) === selectedId) ?? null;
    const [query, setQuery] = useState(seleccionado ? etiqueta(seleccionado) : '');
    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    // Si el colaborador seleccionado cambia desde afuera (ej. al cargar un
    // formulario de edición ya con colaborador_id), refleja su nombre aquí.
    useEffect(() => {
        setQuery(seleccionado ? etiqueta(seleccionado) : '');
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [seleccionado?.id]);

    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const normalizado = query.trim().toLowerCase();
    const resultados = normalizado
        ? colaboradores.filter(
              (c) =>
                  c.nombres.toLowerCase().includes(normalizado) ||
                  c.apellidos.toLowerCase().includes(normalizado) ||
                  c.cedula.toLowerCase().includes(normalizado),
          )
        : colaboradores;

    const seleccionar = (colaborador: ColaboradorOption) => {
        onSelect(colaborador);
        setQuery(etiqueta(colaborador));
        setOpen(false);
    };

    return (
        <div ref={containerRef} className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <div className="relative">
                <Search className="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    id={id}
                    className="pl-8"
                    autoComplete="off"
                    placeholder="Buscar por nombre o identificación..."
                    value={query}
                    disabled={disabled}
                    onChange={(e) => {
                        setQuery(e.target.value);
                        setOpen(true);
                        if (seleccionado) onSelect(null);
                    }}
                    onFocus={() => setOpen(true)}
                />
                {open && !disabled && (
                    <div className="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-md border border-border bg-popover p-1 shadow-md">
                        {resultados.length === 0 ? (
                            <p className="px-2 py-1.5 text-sm text-muted-foreground">Sin resultados</p>
                        ) : (
                            resultados.map((colaborador) => (
                                <button
                                    key={colaborador.id}
                                    type="button"
                                    onClick={() => seleccionar(colaborador)}
                                    className={`flex w-full items-center justify-between gap-2 rounded-sm px-2 py-1.5 text-left text-sm transition-colors hover:bg-accent hover:text-accent-foreground ${
                                        String(colaborador.id) === selectedId ? 'bg-accent/60' : ''
                                    }`}
                                >
                                    <span className="flex min-w-0 items-center gap-2">
                                        <User className="size-3.5 shrink-0 text-muted-foreground" />
                                        <span className="truncate">
                                            {colaborador.nombres} {colaborador.apellidos}
                                        </span>
                                    </span>
                                    <span className="shrink-0 text-xs text-muted-foreground">
                                        {colaborador.cedula}
                                        {colaborador.turno ? ` · ${TURNO_LABELS[colaborador.turno]}` : ''}
                                    </span>
                                </button>
                            ))
                        )}
                    </div>
                )}
            </div>
            <InputError message={error} />
        </div>
    );
}
