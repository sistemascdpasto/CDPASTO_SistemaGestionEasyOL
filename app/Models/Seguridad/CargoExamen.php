<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CargoExamen extends Model
{
    protected $table = 'cargo_examenes';

    protected $fillable = ['cargo', 'examen_id', 'tipo_evaluacion', 'obligatorio', 'activo'];

    protected function casts(): array
    {
        return ['obligatorio' => 'boolean', 'activo' => 'boolean'];
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class);
    }
}
