<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->completed(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->sentence(),
            'is_hidden' => false,
        ];
    }
}
