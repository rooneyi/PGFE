<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

final class AdminUserController extends Controller
{
    /** Rôles d'agents d'école que super-admin / admin peuvent créer. */
    private const SCHOOL_AGENT_ROLES = [
        'admin-ecole',
        'enseignant',
        'comptable',
        'stoker',
        'rh',
        'inspecteur',
        'disciplinaire',
        'prefet',
        'tiers',
    ];

    public function index()
    {
        /** @var User|null $current */
        $current = Auth::user();
        if (! $current || (! $this->isAdminActor($current) && ! $current->can('users.view'))) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $query = User::with('roles');

        if (! $this->canCreateSchoolAgents($current)) {
            $schoolId = $current->school_id;
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            }
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        /** @var User|null $current */
        $current = Auth::user();
        if (! $current) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $canCreateAgents = $this->canCreateSchoolAgents($current);
        $canCreateTiers = $this->canCreateTiers($current);

        if (! $canCreateAgents && ! $canCreateTiers) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['nullable', 'string', Rule::in(self::SCHOOL_AGENT_ROLES)],
            'school_id' => ['sometimes', 'integer', 'exists:schools,id'],
        ]);

        $role = $data['role'] ?? 'tiers';

        if (! $canCreateAgents) {
            if (in_array($role, ['admin', 'admin-ecole', 'super-admin'], true)) {
                return response()->json(['message' => 'Non autorisé à créer ce type d\'utilisateur'], 403);
            }
            $role = 'tiers';
        }

        if (in_array($role, ['admin', 'super-admin'], true)) {
            return response()->json(['message' => 'Création d\'un second admin via API restreinte'], 403);
        }

        $newUserSchoolId = $this->resolveSchoolIdForCreate($current, $data, $canCreateAgents);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        if ($newUserSchoolId !== null) {
            $payload['school_id'] = $newUserSchoolId;
        }

        $user = User::create($payload);
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return response()->json($user->load('roles'), 201);
    }

    public function update(Request $request, User $user)
    {
        /** @var User|null $current */
        $current = Auth::user();
        if (! $current || (! $this->isAdminActor($current) && ! $current->can('users.update'))) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:6'],
            'role' => ['sometimes', 'string', Rule::in(self::SCHOOL_AGENT_ROLES)],
            'school_id' => ['sometimes', 'integer', 'exists:schools,id'],
        ]);

        $canCreateAgents = $this->canCreateSchoolAgents($current);

        if (isset($data['role'])) {
            if (! $canCreateAgents) {
                if ($data['role'] !== 'tiers') {
                    return response()->json(['message' => 'Non autorisé (modification rôle)'], 403);
                }
                if ($user->hasAnyRole(['admin', 'super-admin'])) {
                    return response()->json(['message' => 'Non autorisé (cible admin)'], 403);
                }
            }
            if (in_array($data['role'], ['admin', 'super-admin'], true) && ! $user->hasRole($data['role'])) {
                return response()->json(['message' => 'Promotion admin interdite ici'], 403);
            }
            Role::findOrCreate($data['role'], 'web');
            $user->syncRoles([$data['role']]);
            unset($data['role']);
        }

        if (array_key_exists('school_id', $data)) {
            if (! $canCreateAgents) {
                return response()->json(['message' => 'Non autorisé à modifier l\'école'], 403);
            }
            $user->school_id = $data['school_id'];
            unset($data['school_id']);
        }

        $user->update($data);

        return response()->json($user->fresh()->load('roles'));
    }

    public function assignSchool(Request $request, User $user)
    {
        /** @var User|null $current */
        $current = Auth::user();
        if (! $current || ! $this->canCreateSchoolAgents($current)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $data = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
        ]);

        $old = $user->school_id;
        $user->school_id = $data['school_id'];
        $user->save();

        return response()->json([
            'message' => 'School updated successfully.',
            'user' => $user->load('roles'),
            'old_school_id' => $old,
        ]);
    }

    public function destroy(User $user)
    {
        /** @var User|null $current */
        $current = Auth::user();
        if (! $current || (! $current->can('users.delete') && ! $this->isAdminActor($current))) {
            return response()->json(['message' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }
        if ($user->hasAnyRole(['admin', 'super-admin']) && ! $this->canCreateSchoolAgents($current)) {
            return response()->json(['message' => 'Interdit de supprimer un admin'], Response::HTTP_FORBIDDEN);
        }
        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    private function canCreateSchoolAgents(User $user): bool
    {
        return $user->can('users.create.any')
            || $user->hasAnyRole(['super-admin', 'admin']);
    }

    private function canCreateTiers(User $user): bool
    {
        return $user->can('users.create.tiers')
            || $user->hasAnyRole(['admin-ecole', 'admin', 'super-admin']);
    }

    private function isAdminActor(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'admin-ecole', 'super-admin']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveSchoolIdForCreate(User $current, array $data, bool $canCreateAgents): ?int
    {
        if ($current->hasRole('admin-ecole') || ! $canCreateAgents) {
            return $current->school_id !== null ? (int) $current->school_id : null;
        }

        if (isset($data['school_id'])) {
            return (int) $data['school_id'];
        }

        return $current->school_id !== null ? (int) $current->school_id : null;
    }
}
