<?php

namespace App\Models\Capacitaciones;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapacitacionRevision extends Model
{
    use HasFactory;

    protected $table = 'capacitacion_revisiones';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'material_id',
        'revisada_at',
    ];

    protected $casts = [
        'revisada_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(CapacitacionMaterial::class, 'material_id');
    }
}
