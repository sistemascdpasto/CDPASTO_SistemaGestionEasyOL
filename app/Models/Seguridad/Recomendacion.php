<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;

class Recomendacion extends Model
{
    protected $table = 'recomendaciones';

    protected $fillable = ['nombre', 'categoria', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
