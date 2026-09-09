<?php

namespace App\Models\Seguridad;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlcoholimetroMantenimiento extends Model
{
    protected $table = 'alcoholimetro_mantenimientos';

    protected $fillable = [
        'alcoholimetro_id',
        'fecha',
        'descripcion',
        'realizado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
        ];
    }

    public function alcoholimetro(): BelongsTo
    {
        return $this->belongsTo(Alcoholimetro::class);
    }

    public function realizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'realizado_por');
    }
}
