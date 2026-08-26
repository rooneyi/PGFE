<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Proved;
use App\Models\User;

final class ProvedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin-proved']);
    }

    public function view(User $user, Proved $proved): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('admin-proved') && (int) $user->proved_id === (int) $proved->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, Proved $proved): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('admin-proved') && (int) $user->proved_id === (int) $proved->id;
    }

    public function delete(User $user, Proved $proved): bool
    {
        return $user->hasRole('super-admin');
    }
}
