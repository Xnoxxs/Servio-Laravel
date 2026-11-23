<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory()->published(),
            'customer_id' => User::factory(),
            'scheduled_at' => now()->addDay(),
            'status' => BookingStatus::Pending,
            'price_cents' => fn (array $attributes): int => Service::findOrFail($attributes['service_id'])->price_cents,
            'duration_minutes' => fn (array $attributes): int => Service::findOrFail($attributes['service_id'])->duration_minutes ?? 60,
            'currency' => 'EUR',
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'scheduled_at' => now()->subDay(),
            'status' => BookingStatus::Completed,
        ]);
    }
}
