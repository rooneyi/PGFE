<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SousDivision;
use App\Models\User;

final class SousDivisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin-proved', 'admin-sous-division']);
    }

    public function view(User $user, SousDivision $sousDivision): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->hasRole('admin-sous-division')) {
            return (int) $user->sous_division_id === (int) $sousDivision->id;
        }

        if ($user->hasRole('admin-proved')) {
            return (int) $sousDivision->proved_id === (int) $user->proved_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin-proved']);
    }

    public function update(User $user, SousDivision $sousDivision): bool
    {
        return $this->view($user, $sousDivision)
            && $user->hasAnyRole(['super-admin', 'admin-proved']);
    }

    public function delete(User $user, SousDivision $sousDivision): bool
    {
        return $this->view($user, $sousDivision)
            && $user->hasAnyRole(['super-admin', 'admin-proved']);
    }
}
