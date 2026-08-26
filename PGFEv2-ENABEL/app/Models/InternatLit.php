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

final class InternatLit extends Model
{
    use AutoAssignsSchoolContext;
    use HasUuid;
    use ScopeBySchool;
    use SoftDeletes;

    public const STATUS_LIBRE = 'libre';

    public const STATUS_OCCUPE = 'occupe';

    public const STATUS_HORS_SERVICE = 'hors_service';

    protected $table = 'internat_lits';

    protected $fillable = [
        'school_id',
        'chambre_id',
        'code',
        'status',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function chambre(): BelongsTo
    {
        return $this->belongsTo(InternatChambre::class, 'chambre_id');
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(InternatAffectation::class, 'lit_id');
    }

    public function activeAffectation(): HasMany
    {
        return $this->affectations()->where('statut', InternatAffectation::STATUT_ACTIVE);
    }
}
