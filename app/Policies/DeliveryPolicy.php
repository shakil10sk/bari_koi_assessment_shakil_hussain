<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\User;

class DeliveryPolicy
{
    // Admins can see all deliveries in their tenant; customers/drivers see only their own
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Delivery $delivery): bool
    {
        if ($user->role === 'admin') {
            return $user->tenant_id === $delivery->tenant_id;
        }

        return $user->tenant_id === $delivery->tenant_id
            && ($user->id === $delivery->user_id || $user->id === $delivery->driver_id);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'customer']);
    }

    public function update(User $user, Delivery $delivery): bool
    {
        if ($user->role === 'admin') {
            return $user->tenant_id === $delivery->tenant_id;
        }

        // Drivers can update status of their assigned deliveries
        if ($user->role === 'driver') {
            return $user->tenant_id === $delivery->tenant_id
                && $user->id === $delivery->driver_id;
        }

        return $user->tenant_id === $delivery->tenant_id
            && $user->id === $delivery->user_id;
    }

    public function delete(User $user, Delivery $delivery): bool
    {
        return $user->role === 'admin' && $user->tenant_id === $delivery->tenant_id;
    }

    public function restore(User $user, Delivery $delivery): bool
    {
        return $user->role === 'admin' && $user->tenant_id === $delivery->tenant_id;
    }

    public function forceDelete(User $user, Delivery $delivery): bool
    {
        return $user->role === 'admin' && $user->tenant_id === $delivery->tenant_id;
    }
}
