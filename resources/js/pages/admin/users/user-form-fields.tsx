import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export interface UserFormData {
    first_name: string;
    last_name: string;
    identification_number: string;
    email: string;
    password: string;
    password_confirmation: string;
    roles: string[];
    is_active: boolean;
    [key: string]: string | string[] | boolean;
}

interface UserFormFieldsProps {
    data: UserFormData;
    setData: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void;
    errors: Partial<Record<keyof UserFormData | `roles.${number}`, string>>;
    availableRoles: string[];
    showPassword: boolean;
    processing: boolean;
}

export function UserFormFields({ data, setData, errors, availableRoles, showPassword, processing }: UserFormFieldsProps) {
    const toggleRole = (role: string, checked: boolean) => {
        setData('roles', checked ? [...data.roles, role] : data.roles.filter((r) => r !== role));
    };

    return (
        <div className="grid gap-6">
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="first_name">Nombres</Label>
                    <Input
                        id="first_name"
                        value={data.first_name}
                        onChange={(e) => setData('first_name', e.target.value)}
                        disabled={processing}
                        required
                        autoFocus
                    />
                    <InputError message={errors.first_name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="last_name">Apellidos</Label>
                    <Input id="last_name" value={data.last_name} onChange={(e) => setData('last_name', e.target.value)} disabled={processing} required />
                    <InputError message={errors.last_name} />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="identification_number">Número de identificación</Label>
                    <Input
                        id="identification_number"
                        value={data.identification_number}
                        onChange={(e) => setData('identification_number', e.target.value)}
                        disabled={processing}
                        required
                    />
                    <InputError message={errors.identification_number} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="email">Correo electrónico (opcional)</Label>
                    <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} disabled={processing} />
                    <InputError message={errors.email} />
                </div>
            </div>

            {showPassword && (
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="password">Contraseña</Label>
                        <Input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            disabled={processing}
                            required
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">Confirmar contraseña</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            disabled={processing}
                            required
                        />
                        <InputError message={errors.password_confirmation} />
                    </div>
                </div>
            )}

            <div className="grid gap-2">
                <Label>Roles</Label>
                <div className="grid gap-2 sm:grid-cols-2">
                    {availableRoles.map((role) => (
                        <div key={role} className="flex items-center space-x-2">
                            <Checkbox
                                id={`role-${role}`}
                                checked={data.roles.includes(role)}
                                onCheckedChange={(checked) => toggleRole(role, checked === true)}
                                disabled={processing}
                            />
                            <Label htmlFor={`role-${role}`} className="font-normal">
                                {role}
                            </Label>
                        </div>
                    ))}
                </div>
                <InputError message={errors.roles} />
            </div>

            <div className="flex items-center space-x-2">
                <Checkbox
                    id="is_active"
                    checked={data.is_active}
                    onCheckedChange={(checked) => setData('is_active', checked === true)}
                    disabled={processing}
                />
                <Label htmlFor="is_active" className="font-normal">
                    Usuario activo
                </Label>
            </div>
        </div>
    );
}
