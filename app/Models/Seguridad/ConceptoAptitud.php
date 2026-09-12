<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;

class ConceptoAptitud extends Model
{
    protected $table = 'conceptos_aptitud';

    protected $fillable = ['nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
