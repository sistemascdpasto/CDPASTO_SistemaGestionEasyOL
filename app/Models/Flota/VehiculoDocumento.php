<?php

namespace App\Models\Flota;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiculoDocumento extends Model
{
    protected $table = 'vehiculo_documentos';

    protected $fillable = [
        'vehiculo_id',
        'campo',
        'path',
        'fecha_documento',
    ];

    protected function casts(): array
    {
        return [
            'fecha_documento' => 'date:Y-m-d',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }
}
