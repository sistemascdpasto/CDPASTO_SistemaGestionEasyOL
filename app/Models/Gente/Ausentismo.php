<?php

namespace App\Models\Gente;

use App\Models\Seguridad\Colaborador;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ausentismo extends Model
{
    use HasFactory;

    protected $table = 'ausentismos';

    protected $fillable = [
        'colaborador_id',
        'apellidos',
        'nombres',
        'identificador',
        'grupo',
        'fecha',
        'permiso',
        'turno',
        'entro_1',
        'atraso_1',
        'salio_1',
        'adelanto_1',
        'entro_2',
        'atraso_2',
        'salio_2',
        'adelanto_2',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
    ];

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id');
    }
}
