<?php

namespace App\Models\Seguridad;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanAccionOwdSeguimiento extends Model
{
    protected $table = 'plan_accion_owd_seguimientos';

    protected $fillable = [
        'plan_accion_owd_id',
        'responsable_id',
        'estado',
        'observacion',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
        ];
    }

    public function planAccion(): BelongsTo
    {
        return $this->belongsTo(PlanAccionOwd::class, 'plan_accion_owd_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
