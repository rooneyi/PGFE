<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SousDivision extends Model
{
    use HasFactory;

    protected $table = 'sous_divisions';

    protected $fillable = [
        'proved_id',
        'name',
        'code',
    ];

    public function proved(): BelongsTo
    {
        return $this->belongsTo(Proved::class);
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    protected function casts(): array
    {
        return [
            'proved_id' => 'integer',
        ];
    }
}
