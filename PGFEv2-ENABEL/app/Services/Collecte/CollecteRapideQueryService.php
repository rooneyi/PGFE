<?php

declare(strict_types=1);

namespace App\Services\Collecte;

use App\Models\CollecteRapide;
use App\Models\SousDivision;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Requêtes et statistiques Collecte rapide (scopées PROVED).
 */
final class CollecteRapideQueryService
{
    public function baseQueryForUser(User $user): Builder
    {
        $query = CollecteRapide::query()
            ->with(['sousDivision:id,name,code', 'schoolYear:id,name', 'proved:id,name'])
            ->latest();

        if ($user->hasRole('admin-proved') && $user->proved_id) {
            $query->where('proved_id', $user->proved_id);
        }

        return $query;
    }

    public function applyIndexFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('school_year_id')) {
            $query->where('school_year_id', (int) $request->input('school_year_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query;
    }

    /** @return Collection<int, SousDivision> */
    public function sousDivisionsForUser(User $user): Collection
    {
        $query = SousDivision::query()->orderBy('name');

        if ($user->hasRole('admin-proved') && $user->proved_id) {
            $query->where('proved_id', $user->proved_id);
        }

        return $query->get(['id', 'name', 'code', 'proved_id']);
    }

    /**
     * @return array{total: int, draft: int, submitted: int, sous_divisions: int}
     */
    public function statsForUser(User $user, int $sousDivisionsCount): array
    {
        $scopeQuery = CollecteRapide::query();

        if ($user->hasRole('admin-proved') && $user->proved_id) {
            $scopeQuery->where('proved_id', $user->proved_id);
        }

        return [
            'total' => (clone $scopeQuery)->count(),
            'draft' => (clone $scopeQuery)->where('status', CollecteRapide::STATUS_DRAFT)->count(),
            'submitted' => (clone $scopeQuery)->where('status', CollecteRapide::STATUS_SUBMITTED)->count(),
            'sous_divisions' => $sousDivisionsCount,
        ];
    }

    /** @return Collection<int, CollecteRapide> */
    public function submittedForSynthese(int $provedId, int $schoolYearId): Collection
    {
        $query = CollecteRapide::query()
            ->with(['sousDivision:id,name'])
            ->where('status', CollecteRapide::STATUS_SUBMITTED)
            ->where('proved_id', $provedId);

        if ($schoolYearId > 0) {
            $query->where('school_year_id', $schoolYearId);
        }

        return $query->get();
    }

    /** @return Collection<int, CollecteRapide> */
    public function forExport(int $provedId, Request $request): Collection
    {
        $query = CollecteRapide::query()
            ->with(['sousDivision', 'proved.province', 'schoolYear'])
            ->where('proved_id', $provedId);

        if ($request->filled('school_year_id')) {
            $query->where('school_year_id', (int) $request->input('school_year_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query->orderBy('id')->get();
    }

    /** @return Collection<int, CollecteRapide> */
    public function submittedForExport(int $provedId, int $schoolYearId): Collection
    {
        $query = CollecteRapide::query()
            ->with(['sousDivision', 'proved.province', 'schoolYear'])
            ->where('proved_id', $provedId)
            ->where('status', CollecteRapide::STATUS_SUBMITTED);

        if ($schoolYearId > 0) {
            $query->where('school_year_id', $schoolYearId);
        }

        return $query->orderBy('id')->get();
    }
}
