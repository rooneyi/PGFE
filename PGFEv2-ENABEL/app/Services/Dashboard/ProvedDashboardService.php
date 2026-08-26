<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\CollecteRapide;
use App\Models\Proved;
use App\Models\SousDivision;
use App\Models\User;

/**
 * Données du tableau de bord PROVED (collecte + organisation).
 */
final class ProvedDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $provedId = (int) $user->proved_id;
        $proved = Proved::query()->with('province:id,name')->find($provedId);
        $sousDivisions = SousDivision::query()
            ->where('proved_id', $provedId)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $collectes = CollecteRapide::query()
            ->with(['sousDivision:id,name,code', 'schoolYear:id,name'])
            ->where('proved_id', $provedId)
            ->latest()
            ->get();

        $total = $collectes->count();
        $draft = $collectes->where('status', CollecteRapide::STATUS_DRAFT)->count();
        $submitted = $collectes->where('status', CollecteRapide::STATUS_SUBMITTED)->count();
        $sdCount = $sousDivisions->count();
        $completionRate = $sdCount > 0
            ? (int) round(($sousDivisions->filter(function ($sd) use ($collectes) {
                return $collectes->where('sous_division_id', $sd->id)
                    ->where('status', CollecteRapide::STATUS_SUBMITTED)
                    ->isNotEmpty();
            })->count() / $sdCount) * 100)
            : 0;

        $leaderboard = $sousDivisions->map(function ($sd) use ($collectes) {
            $sdCollectes = $collectes->where('sous_division_id', $sd->id);
            $submittedCount = $sdCollectes->where('status', CollecteRapide::STATUS_SUBMITTED)->count();
            $draftCount = $sdCollectes->where('status', CollecteRapide::STATUS_DRAFT)->count();
            $points = ($submittedCount * 100) + ($draftCount * 25) + (int) $sdCollectes->avg(fn ($c) => $c->progressPercent());

            return [
                'name' => $sd->name,
                'code' => $sd->code,
                'submitted' => $submittedCount,
                'draft' => $draftCount,
                'points' => $points,
                'status' => $submittedCount > 0 ? 'submitted' : ($draftCount > 0 ? 'draft' : 'empty'),
            ];
        })->sortByDesc('points')->values();

        $recent = $collectes->take(6)->map(fn ($c) => [
            'id' => $c->id,
            'sd' => $c->sousDivision?->name ?? '—',
            'year' => $c->schoolYear?->name ?? '—',
            'status' => $c->status,
            'progress' => $c->progressPercent(),
            'url' => route('admin.collecte-rapides.step', [$c, max(1, $c->current_step)]),
        ]);

        $avgProgress = $total > 0 ? (int) round($collectes->avg(fn ($c) => $c->progressPercent())) : 0;

        return [
            'proved' => $proved,
            'greeting_name' => explode(' ', (string) $user->name)[0] ?: $user->name,
            'stats' => [
                'total' => $total,
                'draft' => $draft,
                'submitted' => $submitted,
                'sous_divisions' => $sdCount,
                'completion_rate' => $completionRate,
                'avg_progress' => $avgProgress,
            ],
            'leaderboard' => $leaderboard,
            'recent' => $recent,
            'path_done' => $submitted,
            'path_total' => max($sdCount, 1),
        ];
    }
}
