<?php

namespace App\Policies;

use App\Models\ImportJob;
use App\Models\User;

class ImportJobPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    // Only the user who created the import job can view its status
    public function view(User $user, ImportJob $importJob): bool
    {
        return $user->id === $importJob->user_id || $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'customer']);
    }

    public function update(User $user, ImportJob $importJob): bool
    {
        return $user->id === $importJob->user_id || $user->role === 'admin';
    }

    public function delete(User $user, ImportJob $importJob): bool
    {
        return $user->role === 'admin';
    }

    public function restore(User $user, ImportJob $importJob): bool
    {
        return $user->role === 'admin';
    }

    public function forceDelete(User $user, ImportJob $importJob): bool
    {
        return $user->role === 'admin';
    }
}
