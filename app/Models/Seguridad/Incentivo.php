<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incentivo extends Model
{
    protected $table = 'incentivos';

    protected $fillable = [
        'colaborador_id',
        'mes',
        'cedula',
        'nombre',
        'cargo',
        'indicador_1',
        'pilar_1',
        'total_1',
        'meta_1',
        'indicador_2',
        'pilar_2',
        'total_2',
        'meta_2',
        'indicador_3',
        'pilar_3',
        'total_3',
        'meta_3',
        'podium',
    ];

    protected function casts(): array
    {
        return [
            'total_1' => 'decimal:4',
            'meta_1'  => 'decimal:4',
            'total_2' => 'decimal:4',
            'meta_2'  => 'decimal:4',
            'total_3' => 'decimal:4',
            'meta_3'  => 'decimal:4',
        ];
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }
}
