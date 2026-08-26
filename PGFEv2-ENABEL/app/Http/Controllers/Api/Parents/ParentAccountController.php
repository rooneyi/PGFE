<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parents;

use App\Http\Controllers\Controller;
use App\Models\Parents;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ParentAccountController extends Controller
{
    /**
     * Crée (ou rattache) un compte utilisateur pour un parent.
     * Body optionnel: email, password. Sinon email du parent + mot de passe temporaire.
     */
    public function store(Request $request, Parents $parent): JsonResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($parent->user_id) {
            $existing = User::query()->find($parent->user_id);

            return response()->json([
                'status' => true,
                'message' => 'Ce parent a déjà un compte.',
                'data' => [
                    'parent_id' => $parent->id,
                    'user_id' => $parent->user_id,
                    'email' => $existing?->email,
                    'already_exists' => true,
                ],
            ]);
        }

        $email = $data['email'] ?? $parent->email;
        if (! $email) {
            return response()->json([
                'status' => false,
                'message' => 'Un email est requis pour créer le compte parent (renseignez-le sur la fiche ou dans la requête).',
            ], 422);
        }

        $temporaryPassword = $data['password'] ?? ('Parent@'.Str::upper(Str::random(6)));
        $createdNewUser = false;
        $plainPassword = $temporaryPassword;

        try {
            $result = DB::transaction(function () use ($parent, $email, $temporaryPassword, &$createdNewUser) {
                $user = User::query()->where('email', $email)->first();

                if ($user) {
                    $linked = Parents::query()->where('user_id', $user->id)->where('id', '!=', $parent->id)->exists();
                    if ($linked) {
                        throw ValidationException::withMessages([
                            'email' => ['Cet email est déjà lié à un autre parent.'],
                        ]);
                    }

                    if ($parent->school_id && ! $user->school_id) {
                        $user->school_id = $parent->school_id;
                        $user->save();
                    }
                } else {
                    $user = User::query()->create([
                        'name' => trim($parent->name.' '.$parent->firstname.' '.$parent->lastname),
                        'email' => $email,
                        'password' => Hash::make($temporaryPassword),
                        'school_id' => $parent->school_id,
                    ]);
                    $createdNewUser = true;
                }

                if (! $user->hasRole('parent')) {
                    $user->assignRole('parent');
                }

                $parent->user_id = $user->id;
                if (! $parent->email) {
                    $parent->email = $email;
                }
                $parent->save();

                return $user;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'Impossible de créer le compte parent.',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Compte parent créé avec succès.',
            'data' => [
                'parent_id' => $parent->id,
                'user_id' => $result->id,
                'email' => $result->email,
                'temporary_password' => $createdNewUser && ! isset($data['password']) ? $plainPassword : null,
                'already_exists' => false,
            ],
        ], 201);
    }
}
