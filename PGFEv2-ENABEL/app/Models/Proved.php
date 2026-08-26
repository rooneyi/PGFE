<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

final class Proved extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'province_id',
        'address',
        'phone',
        'email',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function sousDivisions(): HasMany
    {
        return $this->hasMany(SousDivision::class);
    }

    public function schools(): HasManyThrough
    {
        return $this->hasManyThrough(School::class, SousDivision::class);
    }

    protected function casts(): array
    {
        return [
            'province_id' => 'integer',
        ];
    }
}
