<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogGeneracionExamenes extends Model
{
    protected $table = 'log_generacion_examenes';

    public const RESULTADO_OK = 'OK';

    public const RESULTADO_SIN_MATRIZ = 'SIN_MATRIZ';

    protected $fillable = [
        'colaborador_id',
        'fecha_evento',
        'resultado',
        'detalle',
    ];

    protected function casts(): array
    {
        return [
            'fecha_evento' => 'datetime',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }
}
