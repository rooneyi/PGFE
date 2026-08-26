<?php

declare(strict_types=1);

namespace App\Services\Parent;

use App\Models\Parents;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

final class ParentChildrenResolver
{
    public function resolveParentProfile(?User $user = null): ?Parents
    {
        $user ??= Auth::user();
        if (! $user) {
            return null;
        }

        return Parents::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * @return Builder<Student>
     */
    public function childrenQuery(Parents $parent): Builder
    {
        return Student::query()
            ->withoutGlobalScopes()
            ->where(function (Builder $q) use ($parent): void {
                $q->where('parents_id', $parent->id)
                    ->orWhere('parent2_id', $parent->id)
                    ->orWhere('parent3_id', $parent->id);
            });
    }

    /**
     * @return Collection<int, Student>
     */
    public function children(Parents $parent): Collection
    {
        return $this->childrenQuery($parent)
            ->with([
                'registration.classroom',
                'registration.schoolYear',
                'school',
            ])
            ->orderBy('name')
            ->get();
    }

    public function ownsChild(Parents $parent, int $studentId): bool
    {
        return $this->childrenQuery($parent)->where('id', $studentId)->exists();
    }

    public function findOwnedChild(Parents $parent, int $studentId): ?Student
    {
        return $this->childrenQuery($parent)
            ->with([
                'registration.classroom',
                'registration.schoolYear',
                'school',
            ])
            ->where('id', $studentId)
            ->first();
    }
}
