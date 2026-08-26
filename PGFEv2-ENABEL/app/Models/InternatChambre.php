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

final class InternatChambre extends Model
{
    use AutoAssignsSchoolContext;
    use HasUuid;
    use ScopeBySchool;
    use SoftDeletes;

    protected $table = 'internat_chambres';

    protected $fillable = [
        'school_id',
        'pavillon_id',
        'name',
        'capacity',
        'gender',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function pavillon(): BelongsTo
    {
        return $this->belongsTo(InternatPavillon::class, 'pavillon_id');
    }

    public function lits(): HasMany
    {
        return $this->hasMany(InternatLit::class, 'chambre_id');
    }
}
