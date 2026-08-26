<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CollecteRapide;
use App\Models\User;

final class CollecteRapidePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isProved($user);
    }

    public function view(User $user, CollecteRapide $collecteRapide): bool
    {
        return $this->owns($user, $collecteRapide);
    }

    public function create(User $user): bool
    {
        return $this->isProved($user);
    }

    public function update(User $user, CollecteRapide $collecteRapide): bool
    {
        return $this->owns($user, $collecteRapide) && $collecteRapide->isDraft();
    }

    public function submit(User $user, CollecteRapide $collecteRapide): bool
    {
        return $this->owns($user, $collecteRapide) && $collecteRapide->isDraft();
    }

    public function reopen(User $user, CollecteRapide $collecteRapide): bool
    {
        return $this->owns($user, $collecteRapide) && $collecteRapide->isSubmitted();
    }

    public function delete(User $user, CollecteRapide $collecteRapide): bool
    {
        return $this->owns($user, $collecteRapide) && $collecteRapide->isDraft();
    }

    private function isProved(User $user): bool
    {
        return $user->hasRole('admin-proved') && (bool) $user->proved_id;
    }

    private function owns(User $user, CollecteRapide $collecteRapide): bool
    {
        return $this->isProved($user)
            && (int) $user->proved_id === (int) $collecteRapide->proved_id;
    }
}
