import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { type DocumentInfo } from '@/pages/seguridad/colaboradores/colaborador-form-fields';
import { errorDeArchivo } from '../utils';
import { SimpleFileField } from './simple-file-field';

const CATEGORIAS: { value: string; label: string }[] = [
    { value: 'A1', label: 'A1 · Motos hasta 125 c.c.' },
    { value: 'A2', label: 'A2 · Motos de más de 125 c.c.' },
    { value: 'B1', label: 'B1 · Automóviles, camperos, camionetas particulares' },
    { value: 'B2', label: 'B2 · Camiones rígidos, busetas y buses particulares' },
    { value: 'B3', label: 'B3 · Vehículos articulados particulares' },
    { value: 'C1', label: 'C1 · Automóviles, camperos, camionetas públicos' },
    { value: 'C2', label: 'C2 · Camiones rígidos, busetas y buses públicos' },
    { value: 'C3', label: 'C3 · Vehículos articulados públicos' },
];

interface LicenciaConduccionCategoriasProps {
    seleccionadas: string[];
    onToggle: (categoria: string) => void;
    archivos: Record<string, File[]>;
    onArchivoChange: (categoria: string, files: File[]) => void;
    existingDocumentos?: Record<string, DocumentInfo[]>;
    errors?: Record<string, string | undefined>;
    disabled?: boolean;
}

/**
 * Grilla de las 8 categorías de licencia de conducción (4.3): cada categoría
 * marcada revela su propio campo de archivo independiente.
 */
export function LicenciaConduccionCategorias({
    seleccionadas,
    onToggle,
    archivos,
    onArchivoChange,
    existingDocumentos,
    errors = {},
    disabled,
}: LicenciaConduccionCategoriasProps) {
    return (
        <div className="grid gap-3">
            {CATEGORIAS.map((categoria) => {
                const activa = seleccionadas.includes(categoria.value);
                const campo = `documento_licencia_${categoria.value.toLowerCase()}`;

                return (
                    <div key={categoria.value} className="rounded-lg border border-border p-3">
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id={`licencia-${categoria.value}`}
                                checked={activa}
                                onCheckedChange={() => onToggle(categoria.value)}
                                disabled={disabled}
                            />
                            <Label htmlFor={`licencia-${categoria.value}`} className="font-normal">
                                {categoria.label}
                            </Label>
                        </div>
                        {activa && (
                            <div className="mt-2 pl-6">
                                <SimpleFileField
                                    files={archivos[categoria.value] ?? []}
                                    existing={existingDocumentos?.[campo]}
                                    onChange={(files) => onArchivoChange(categoria.value, files)}
                                    error={errorDeArchivo(errors, campo)}
                                    disabled={disabled}
                                />
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
