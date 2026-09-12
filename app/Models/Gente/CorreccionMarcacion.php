<?php

namespace App\Models\Gente;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorreccionMarcacion extends Model
{
    protected $table = 'correcciones_marcaciones';

    protected $fillable = [
        'identificacion',
        'fecha',
        'hora',
        'tipo',
        'centro_costo',
        'comentario',
        'nombre_completo',
        'cargo',
        'colaborador_encontrado',
        'error_validacion',
        'usuario_importo_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha'                  => 'date:Y-m-d',
            'colaborador_encontrado' => 'boolean',
        ];
    }

    public function usuarioImporto(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_importo_id');
    }
}
