<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CollecteRapide extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    protected $fillable = [
        'proved_id',
        'sous_division_id',
        'school_year_id',
        'status',
        'current_step',
        'data',
        'created_by',
    ];

    public function proved(): BelongsTo
    {
        return $this->belongsTo(Proved::class);
    }

    public function sousDivision(): BelongsTo
    {
        return $this->belongsTo(SousDivision::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function progressPercent(): int
    {
        $last = max(array_keys(config('collecte_rapide.steps', [0 => true])));

        if ($last <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->current_step / $last) * 100));
    }

    protected function casts(): array
    {
        return [
            'proved_id' => 'integer',
            'sous_division_id' => 'integer',
            'school_year_id' => 'integer',
            'current_step' => 'integer',
            'created_by' => 'integer',
            'data' => 'array',
        ];
    }
}
