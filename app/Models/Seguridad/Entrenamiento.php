<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entrenamiento extends Model
{
    protected $table = 'entrenamientos';

    protected $fillable = ['nombre'];

    public function registros(): HasMany
    {
        return $this->hasMany(ColaboradorEntrenamiento::class);
    }
}
