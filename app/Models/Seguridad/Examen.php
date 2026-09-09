<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examen extends Model
{
    protected $table = 'examenes';

    protected $fillable = ['nombre', 'categoria', 'tipo_examen', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function cargoExamenes(): HasMany
    {
        return $this->hasMany(CargoExamen::class);
    }
}
