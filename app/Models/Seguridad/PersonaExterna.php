<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Contratista o personal externo (no colaborador) al que se le practica la
 * prueba de alcoholemia al ingresar o permanecer en CD Nariño.
 */
class PersonaExterna extends Model
{
    protected $table = 'personas_externas';

    public const TIPOS_VINCULO = [
        'contratista' => 'Contratista',
        'visitante' => 'Visitante',
        'proveedor' => 'Proveedor',
        'transportador' => 'Transportador tercero',
        'otro' => 'Otro',
    ];

    protected $fillable = [
        'documento',
        'nombres',
        'apellidos',
        'empresa',
        'tipo_vinculo',
        'cargo',
        'telefono',
    ];

    protected $appends = ['nombre_completo', 'tipo_vinculo_label'];

    public function pruebasAlcoholemia(): HasMany
    {
        return $this->hasMany(PruebaAlcoholemia::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    public function getTipoVinculoLabelAttribute(): string
    {
        return self::TIPOS_VINCULO[$this->tipo_vinculo] ?? ucfirst((string) $this->tipo_vinculo);
    }
}
