<?php

namespace App\Models\Gente;

use App\Models\Seguridad\Colaborador;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DpoAcademy extends Model
{
    use HasFactory;

    protected $table = 'dpo_academy';

    protected $fillable = [
        'colaborador_id',
        'region',
        'centro',
        'negocio',
        'qr_safety',
        'nombre',
        'cargo',
        'coronita',
        'calificacion',
        'status',
    ];

    protected $casts = [
        'calificacion' => 'float',
    ];

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_id');
    }
}
