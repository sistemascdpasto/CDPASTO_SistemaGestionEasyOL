<?php

namespace App\Models\Seguridad;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColaboradorEntrenamiento extends Model
{
    protected $table = 'colaborador_entrenamientos';

    protected $fillable = [
        'colaborador_id',
        'entrenamiento_id',
        'fecha_registro',
        'hora_registro',
        'registrado_por_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'date:Y-m-d',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function entrenamiento(): BelongsTo
    {
        return $this->belongsTo(Entrenamiento::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
