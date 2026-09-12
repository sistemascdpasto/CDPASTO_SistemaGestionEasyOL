<?php

namespace App\Models\Seguridad;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CondicionSalud extends Model
{
    protected $table = 'condiciones_salud';

    protected $fillable = [
        'colaborador_id',
        'prueba_alcoholemia_id',
        'momento',
        'estado',
        'observacion',
        'responsable_id',
        'fecha_hora',
        'consentimiento_aceptado',
        'consentimiento_en',
        'consentimiento_ip',
        'consentimiento_texto_version',
        'firma_supervisor_path',
        'firmado_por_id',
        'firmado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'consentimiento_aceptado' => 'boolean',
            'consentimiento_en' => 'datetime',
            'firmado_en' => 'datetime',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function pruebaAlcoholemia(): BelongsTo
    {
        return $this->belongsTo(PruebaAlcoholemia::class);
    }

    public function firmadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'firmado_por_id');
    }
}
