<?php

declare(strict_types=1);

namespace App\Services\Organization;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Session;

final class SchoolScopeResolver
{
    /**
     * @return int[]|null null = aucun filtre (super-admin)
     */
    public function allowedSchoolIds(?User $user = null): ?array
    {
        $user ??= auth()->user();
        if (! $user) {
            return [];
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return null;
        }

        $ids = match (true) {
            method_exists($user, 'hasRole') && $user->hasRole('admin-proved') => $this->schoolIdsForProved($user),
            method_exists($user, 'hasRole') && $user->hasRole('admin-sous-division') => $this->schoolIdsForSousDivision($user),
            default => $user->school_id ? [(int) $user->school_id] : [],
        };

        if ($ids === []) {
            return [];
        }

        $ids = array_map(intval(...), $ids);

        if ($user->hasRole('admin-proved')) {
            $sousDivisionId = Session::get('selected_sous_division_id');
            if ($sousDivisionId) {
                $ids = School::query()
                    ->where('sous_division_id', (int) $sousDivisionId)
                    ->whereIn('id', $ids)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }
        }

        $selectedSchoolId = Session::get('selected_school_id');
        if ($selectedSchoolId && in_array((int) $selectedSchoolId, $ids, true)) {
            return [(int) $selectedSchoolId];
        }

        return $ids;
    }

    public function canAccessSchool(int $schoolId, ?User $user = null): bool
    {
        $allowed = $this->allowedSchoolIds($user);
        if ($allowed === null) {
            return true;
        }

        return in_array($schoolId, $allowed, true);
    }

    /**
     * Applique / impose sous_division_id lors de la création ou mise à jour d'une école.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applySousDivisionToSchoolData(array $data, ?User $user = null): array
    {
        $user ??= auth()->user();
        if (! $user) {
            return $data;
        }

        if ($user->hasRole('admin-sous-division') && $user->sous_division_id) {
            $data['sous_division_id'] = (int) $user->sous_division_id;
        } elseif (! empty($data['sous_division_id'])) {
            $sousDivisionId = (int) $data['sous_division_id'];
            if ($user->hasRole('admin-proved') || $user->hasRole('admin-sous-division')) {
                abort_unless($this->canAccessSousDivision($sousDivisionId, $user), 403);
            }
            $data['sous_division_id'] = $sousDivisionId;
        } else {
            // Optional on create/update: omit or leave null without forcing a value.
            unset($data['sous_division_id']);
        }

        return $data;
    }

    public function canAccessSousDivision(int $sousDivisionId, ?User $user = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->hasRole('admin-sous-division')) {
            return (int) $user->sous_division_id === $sousDivisionId;
        }

        if ($user->hasRole('admin-proved')) {
            return \App\Models\SousDivision::query()
                ->where('id', $sousDivisionId)
                ->where('proved_id', $user->proved_id)
                ->exists();
        }

        return false;
    }

    /** @return int[] */
    private function schoolIdsForProved(User $user): array
    {
        if (! $user->proved_id) {
            return [];
        }

        return School::query()
            ->whereHas('sousDivision', fn ($q) => $q->where('proved_id', $user->proved_id))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return int[] */
    private function schoolIdsForSousDivision(User $user): array
    {
        if (! $user->sous_division_id) {
            return [];
        }

        return School::query()
            ->where('sous_division_id', $user->sous_division_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
