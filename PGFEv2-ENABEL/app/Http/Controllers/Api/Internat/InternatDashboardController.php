<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internat;

use App\Http\Controllers\Controller;
use App\Models\InternatAffectation;
use App\Models\InternatChambre;
use App\Models\InternatLit;
use App\Models\InternatPavillon;
use Illuminate\Http\JsonResponse;

final class InternatDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalPavillons = InternatPavillon::count();
        $totalChambres = InternatChambre::count();
        $totalLits = InternatLit::count();
        $litsLibres = InternatLit::where('status', InternatLit::STATUS_LIBRE)->count();
        $litsOccupes = InternatLit::where('status', InternatLit::STATUS_OCCUPE)->count();
        $litsHorsService = InternatLit::where('status', InternatLit::STATUS_HORS_SERVICE)->count();
        $internesActifs = InternatAffectation::where('statut', InternatAffectation::STATUT_ACTIVE)->count();

        $occupationRate = $totalLits > 0
            ? round(($litsOccupes / $totalLits) * 100, 1)
            : 0;

        $recentAffectations = InternatAffectation::query()
            ->with([
                'student:id,firstname,lastname,matricule',
                'lit:id,code,chambre_id',
                'lit.chambre:id,name',
            ])
            ->where('statut', InternatAffectation::STATUT_ACTIVE)
            ->orderByDesc('date_entree')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Tableau de bord internat récupéré avec succès.',
            'data' => [
                'total_pavillons' => $totalPavillons,
                'total_chambres' => $totalChambres,
                'total_lits' => $totalLits,
                'lits_libres' => $litsLibres,
                'lits_occupes' => $litsOccupes,
                'lits_hors_service' => $litsHorsService,
                'internes_actifs' => $internesActifs,
                'taux_occupation' => $occupationRate,
                'affectations_recentes' => $recentAffectations,
            ],
        ]);
    }
}
