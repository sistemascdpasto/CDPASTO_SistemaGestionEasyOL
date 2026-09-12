<?php

namespace App\Models\Gente;

use App\Models\Seguridad\Colaborador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColaboradorCalificacion extends Model
{
    protected $table = 'colaborador_calificaciones';

    protected $fillable = [
        'colaborador_id',
        'identificacion',
        'colaborador',
        'cargo',
        'centro_distribucion',
        'modulo_id_externo',
        'modulo',
        'nota_modulo',
    ];

    protected function casts(): array
    {
        return [
            'nota_modulo' => 'decimal:2',
        ];
    }

    public function colaboradorRelacion(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id');
    }
}
