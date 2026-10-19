import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ColaboradorSearchSelect } from '@/pages/seguridad/pruebas/colaborador-search-select';

export interface PersonaExternaOption {
    id: number;
    documento: string;
    nombres: string;
    apellidos: string | null;
    empresa: string;
    tipo_vinculo: string;
    cargo: string | null;
    telefono: string | null;
}

export interface TipoVinculoOption {
    value: string;
    label: string;
}

export interface PersonaExternaValues {
    externo_documento: string;
    externo_nombres: string;
    externo_apellidos: string;
    externo_empresa: string;
    externo_tipo_vinculo: string;
    externo_cargo: string;
    externo_telefono: string;
}

export const personaExternaVacia: PersonaExternaValues = {
    externo_documento: '',
    externo_nombres: '',
    externo_apellidos: '',
    externo_empresa: '',
    externo_tipo_vinculo: 'contratista',
    externo_cargo: '',
    externo_telefono: '',
};

export function valoresDesdePersona(persona: PersonaExternaOption): PersonaExternaValues {
    return {
        externo_documento: persona.documento,
        externo_nombres: persona.nombres,
        externo_apellidos: persona.apellidos ?? '',
        externo_empresa: persona.empresa,
        externo_tipo_vinculo: persona.tipo_vinculo,
        externo_cargo: persona.cargo ?? '',
        externo_telefono: persona.telefono ?? '',
    };
}

/**
 * Datos del contratista / personal externo evaluado. Se puede buscar a
 * alguien ya registrado (precarga sus datos) o diligenciar uno nuevo; el
 * backend lo identifica por documento, así que escribir un documento ya
 * existente también reutiliza el registro.
 */
export function PersonaExternaFields({
    personasExternas,
    tiposVinculo,
    values,
    onChange,
    onSeleccionarExistente,
    errors,
}: {
    personasExternas: PersonaExternaOption[];
    tiposVinculo: TipoVinculoOption[];
    values: PersonaExternaValues;
    onChange: (campo: keyof PersonaExternaValues, valor: string) => void;
    onSeleccionarExistente: (persona: PersonaExternaOption | null) => void;
    errors: Partial<Record<keyof PersonaExternaValues, string>>;
}) {
    const existente = personasExternas.find((p) => p.documento === values.externo_documento.trim()) ?? null;

    return (
        <div className="grid gap-4">
            {personasExternas.length > 0 && (
                <ColaboradorSearchSelect
                    id="externo_search"
                    label="Buscar persona externa ya registrada (opcional)"
                    colaboradores={personasExternas.map((p) => ({
                        id: p.id,
                        nombres: p.nombres,
                        apellidos: `${p.apellidos ?? ''} · ${p.empresa}`.trim(),
                        cedula: p.documento,
                        turno: null,
                        cargo: p.cargo,
                    }))}
                    selectedId={existente ? String(existente.id) : ''}
                    onSelect={(opcion) => onSeleccionarExistente(opcion ? (personasExternas.find((p) => p.id === opcion.id) ?? null) : null)}
                />
            )}

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div className="grid gap-2">
                    <Label htmlFor="externo_documento">Documento de identidad</Label>
                    <Input
                        id="externo_documento"
                        inputMode="numeric"
                        autoComplete="off"
                        value={values.externo_documento}
                        onChange={(e) => {
                            const documento = e.target.value;
                            const coincide = personasExternas.find((p) => p.documento === documento.trim());
                            if (coincide) {
                                onSeleccionarExistente(coincide);
                            } else {
                                onChange('externo_documento', documento);
                            }
                        }}
                    />
                    {existente && <p className="text-xs text-emerald-700 dark:text-emerald-400">Persona ya registrada: se reutilizan sus datos.</p>}
                    <InputError message={errors.externo_documento} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="externo_nombres">Nombres</Label>
                    <Input id="externo_nombres" value={values.externo_nombres} onChange={(e) => onChange('externo_nombres', e.target.value)} />
                    <InputError message={errors.externo_nombres} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="externo_apellidos">Apellidos</Label>
                    <Input id="externo_apellidos" value={values.externo_apellidos} onChange={(e) => onChange('externo_apellidos', e.target.value)} />
                    <InputError message={errors.externo_apellidos} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="externo_empresa">Empresa contratista</Label>
                    <Input id="externo_empresa" value={values.externo_empresa} onChange={(e) => onChange('externo_empresa', e.target.value)} />
                    <InputError message={errors.externo_empresa} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="externo_tipo_vinculo">Tipo de vínculo</Label>
                    <Select value={values.externo_tipo_vinculo} onValueChange={(value) => onChange('externo_tipo_vinculo', value)}>
                        <SelectTrigger id="externo_tipo_vinculo">
                            <SelectValue placeholder="Selecciona" />
                        </SelectTrigger>
                        <SelectContent>
                            {tiposVinculo.map((tipo) => (
                                <SelectItem key={tipo.value} value={tipo.value}>
                                    {tipo.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.externo_tipo_vinculo} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="externo_cargo">Cargo / labor (opcional)</Label>
                    <Input id="externo_cargo" value={values.externo_cargo} onChange={(e) => onChange('externo_cargo', e.target.value)} />
                    <InputError message={errors.externo_cargo} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="externo_telefono">Teléfono (opcional)</Label>
                    <Input
                        id="externo_telefono"
                        type="tel"
                        value={values.externo_telefono}
                        onChange={(e) => onChange('externo_telefono', e.target.value)}
                    />
                    <InputError message={errors.externo_telefono} />
                </div>
            </div>
        </div>
    );
}
