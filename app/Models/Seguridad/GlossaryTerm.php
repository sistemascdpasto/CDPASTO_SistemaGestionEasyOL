<?php

namespace App\Models\Seguridad;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlossaryTerm extends Model
{
    use SoftDeletes;

    protected $table = 'glossary_terms';

    protected $fillable = [
        'nombre',
        'definicion',
        'categoria',
        'pregunta_numero',
        'representacion',
        'enlaces_de_interes',
        'source',
        'created_by',
        'updated_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeByCategory(Builder $query, string $categoria): Builder
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('nombre', 'like', "%{$search}%")
              ->orWhere('definicion', 'like', "%{$search}%");
        });
    }

    public function isFromWeb(): bool
    {
        return $this->source === 'scraped';
    }

    public function isManual(): bool
    {
        return $this->source === 'manual';
    }
}
