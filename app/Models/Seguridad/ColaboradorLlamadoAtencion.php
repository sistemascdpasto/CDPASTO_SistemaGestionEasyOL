<?php

namespace App\Models\Seguridad;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColaboradorLlamadoAtencion extends Model
{
    protected $table = 'colaborador_llamados_atencion';

    protected $fillable = [
        'colaborador_id',
        'observacion',
        'path',
        'registrado_por_id',
        'fecha_hora',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
