<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Service;
use App\Models\User;

class BookingPolicy
{
    public function create(User $user, Service $service): bool
    {
        return $service->is_published && $service->archived_at === null;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->customer_id;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking);
    }

    public function review(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking)
            && $booking->status === BookingStatus::Completed
            && $booking->service->provider_id !== $user->id;
    }

    public function manage(User $user, Booking $booking): bool
    {
        return $user->id === $booking->service->provider_id;
    }
}
