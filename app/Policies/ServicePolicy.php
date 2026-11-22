<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function view(?User $user, Service $service): bool
    {
        return $service->is_published || ($user !== null && $this->update($user, $service));
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function update(User $user, Service $service): bool
    {
        return $user->id === $service->provider_id && $service->archived_at === null;
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }

    public function unpublish(User $user, Service $service): bool
    {
        return $user->is_admin === true;
    }
}
