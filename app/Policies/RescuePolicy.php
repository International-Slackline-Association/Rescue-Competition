<?php

namespace App\Policies;

use App\Models\Rescue;
use App\Models\User;

class RescuePolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_admin ?: null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Rescue $rescue): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Rescue $rescue): bool
    {
        return $rescue->created_by === $user->id || $rescue->involves($user);
    }

    public function delete(User $user, Rescue $rescue): bool
    {
        return false;
    }
}
