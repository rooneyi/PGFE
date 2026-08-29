<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\System\DataPurgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DataPurgeController extends Controller
{
    public function __construct(
        private readonly DataPurgeService $purgeService,
    ) {}

    /**
     * Purge almost all application data (super-admin only).
     * Body must include confirmation: "PURGER".
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->hasRole('super-admin')) {
            return response()->json([
                'status' => false,
                'message' => 'Accès réservé au super-admin.',
            ], 403);
        }

        $request->validate([
            'confirmation' => ['required', 'string', 'in:PURGER'],
        ], [
            'confirmation.required' => 'La confirmation est obligatoire.',
            'confirmation.in' => 'Tapez exactement PURGER pour confirmer.',
        ]);

        try {
            $result = $this->purgeService->purge($user);

            Log::warning('Super-admin data purge executed', [
                'actor_id' => $user->id,
                'actor_email' => $user->email,
                'deleted_users' => $result['deleted_users'],
                'tables_count' => count($result['purged_tables']),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Données purgées avec succès. Les pays, provinces, territoires, communes et comptes admin ont été conservés.',
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            Log::error('Data purge failed', [
                'actor_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Échec de la purge : '.$e->getMessage(),
            ], 500);
        }
    }
}
