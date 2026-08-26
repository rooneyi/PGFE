<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AutoAssignsSchoolContext;
use App\Models\Concerns\HasUuid;
use App\Models\Concerns\ScopeBySchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InternatAffectation extends Model
{
    use AutoAssignsSchoolContext;
    use HasUuid;
    use ScopeBySchool;
    use SoftDeletes;

    public const STATUT_ACTIVE = 'active';

    public const STATUT_TERMINEE = 'terminee';

    protected $table = 'internat_affectations';

    protected $fillable = [
        'school_id',
        'school_year_id',
        'student_id',
        'lit_id',
        'date_entree',
        'date_sortie',
        'statut',
        'notes',
    ];

    protected $casts = [
        'date_entree' => 'date',
        'date_sortie' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function lit(): BelongsTo
    {
        return $this->belongsTo(InternatLit::class, 'lit_id');
    }
}
