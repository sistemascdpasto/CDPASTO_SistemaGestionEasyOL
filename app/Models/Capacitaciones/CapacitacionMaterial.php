<?php

namespace App\Models\Capacitaciones;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapacitacionMaterial extends Model
{
    use HasFactory;

    protected $table = 'capacitacion_materiales';

    protected $fillable = [
        'carpeta_id',
        'titulo',
        'descripcion',
        'tipo',
        'archivo_path',
        'archivo_nombre_original',
        'tamano_bytes',
        'mime_type',
        'enlace_externo',
        'estado',
        'destacada',
        'fecha_programada',
        'orden',
        'created_by',
    ];

    protected $casts = [
        'destacada' => 'boolean',
        'fecha_programada' => 'date:Y-m-d',
    ];

    protected $appends = [
        'tamano_humano',
    ];

    public function carpeta(): BelongsTo
    {
        return $this->belongsTo(CapacitacionCarpeta::class, 'carpeta_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revisiones(): HasMany
    {
        return $this->hasMany(CapacitacionRevision::class, 'material_id');
    }

    public function getTamanoHumanoAttribute(): ?string
    {
        if (! $this->tamano_bytes) {
            return null;
        }

        $bytes = $this->tamano_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return number_format($bytes / pow(1024, $power), 1, '.', '') . ' ' . ($units[$power] ?? 'B');
    }
}
