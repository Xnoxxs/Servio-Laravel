<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'provider_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'price_cents' => fake()->numberBetween(1500, 15000),
            'duration_minutes' => 60,
            'location' => fake()->city(),
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }
}
