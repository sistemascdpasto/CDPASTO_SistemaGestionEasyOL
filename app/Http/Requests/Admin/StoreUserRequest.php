<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'identification_number' => [
                'required', 'string', 'max:30',
                Rule::unique('users', 'identification_number')->whereNull('deleted_at'),
            ],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'confirmed', 'min:8'],
            'roles' => ['required', 'array', 'min:1'],
            // 'Reparto' existe en la tabla `roles` pero el pilar está
            // desactivado (ver config/modules.php): no se puede asignar.
            'roles.*' => ['string', Rule::exists('roles', 'name'), Rule::notIn(config('modules.reparto_habilitado') ? [] : ['Reparto'])],
            'is_active' => ['boolean'],
        ];
    }
}
