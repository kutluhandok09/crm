<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isReseller();
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $this->manage($user, $tenant);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isReseller();
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $this->manage($user, $tenant);
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $this->manage($user, $tenant);
    }

    public function manageUsers(User $user, Tenant $tenant): bool
    {
        return $this->manage($user, $tenant);
    }

    protected function manage(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin()
            || ($user->isReseller() && (int) $tenant->reseller_id === (int) $user->getKey());
    }
}
