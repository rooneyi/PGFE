<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AutoAssignsSchoolContext;
use App\Models\Concerns\ScopeBySchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Parents extends Model
{
    use AutoAssignsSchoolContext;
    use HasFactory;
    use ScopeBySchool;
    use SoftDeletes;
    use \App\Models\Concerns\HasUuid;

    protected $fillable = [
        'id',
        'name',
        'firstname',
        'lastname',
        'genre',
        'phone_number',
        'identity_card',
        'email',
        'school_id',
        'user_id',
        'created_at',
        'updated_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Enfants où ce parent est le parent principal. */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'parents_id');
    }

    /** Tous les enfants liés (parent 1, 2 ou 3). */
    public function allChildren(): Builder
    {
        return Student::query()->where(function (Builder $q): void {
            $q->where('parents_id', $this->id)
                ->orWhere('parent2_id', $this->id)
                ->orWhere('parent3_id', $this->id);
        });
    }
}
