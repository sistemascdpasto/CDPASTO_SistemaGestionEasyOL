<?php

namespace App\Enums;

enum Role: string
{
    case Administrador = 'Administrador';
    case Seguridad = 'Seguridad';
    case Gente = 'Gente';
    case Flota = 'Flota';
    case Colaborador = 'Colaborador';

    /**
     * Roles that map 1:1 to a business module slug (excludes Administrador).
     * Colaborador is intentionally excluded: it is a self-service portal role,
     * not a generic module.
     *
     * @return array<string, self>
     */
    public static function moduleRoles(): array
    {
        return [
            'seguridad' => self::Seguridad,
            'gente'     => self::Gente,
            'flota'     => self::Flota,
        ];
    }

    public static function forModuleSlug(string $slug): ?self
    {
        return self::moduleRoles()[$slug] ?? null;
    }
}
