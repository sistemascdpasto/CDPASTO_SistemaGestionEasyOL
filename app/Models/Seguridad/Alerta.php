<?php

namespace App\Models\Seguridad;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerta extends Model
{
    protected $table = 'alertas';

    protected $fillable = [
        'tipo',
        'colaborador_id',
        'alcoholimetro_id',
        'prueba_alcoholemia_id',
        'mensaje',
        'atendida',
        'atendida_por',
        'atendida_en',
    ];

    protected function casts(): array
    {
        return [
            'atendida' => 'boolean',
            'atendida_en' => 'datetime',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function alcoholimetro(): BelongsTo
    {
        return $this->belongsTo(Alcoholimetro::class);
    }

    public function pruebaAlcoholemia(): BelongsTo
    {
        return $this->belongsTo(PruebaAlcoholemia::class);
    }

    public function atendidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendida_por');
    }
}
