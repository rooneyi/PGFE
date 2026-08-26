<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AutoAssignsSchoolContext;
use App\Models\Concerns\HasUuid;
use App\Models\Concerns\ScopeBySchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InternatPavillon extends Model
{
    use AutoAssignsSchoolContext;
    use HasUuid;
    use ScopeBySchool;
    use SoftDeletes;

    protected $table = 'internat_pavillons';

    protected $fillable = [
        'school_id',
        'name',
        'gender',
        'notes',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function chambres(): HasMany
    {
        return $this->hasMany(InternatChambre::class, 'pavillon_id');
    }
}
