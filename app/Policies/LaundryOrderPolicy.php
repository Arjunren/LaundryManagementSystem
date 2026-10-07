<?php

namespace App\Policies;

use App\Models\LaundryOrder;
use App\Models\User;

class LaundryOrderPolicy
{
    public function view(User $user, LaundryOrder $order): bool
    {
        return $user->isStaff() || $order->customer_id === $user->id;
    }
}
