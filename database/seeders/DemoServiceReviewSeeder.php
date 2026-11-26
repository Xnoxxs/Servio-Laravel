<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoServiceReviewSeeder extends Seeder
{
    private const AWAITING_CUSTOMER_REVIEW = [
        'Apartment cleaning',
        'Small apartment move',
        'Patio planter refresh',
    ];

    private const REVIEW_COUNTS = [
        'Exterior car wash' => 12,
        'Interior car detailing' => 5,
        'Apartment cleaning' => 20,
        'Kitchen deep clean' => 8,
        'Move-out cleaning' => 6,
        'Dripping tap repair' => 11,
        'Slow sink drain clearing' => 4,
        'Ceiling light installation' => 9,
        'Wall socket replacement' => 3,
        'Packing assistance' => 7,
        'Small apartment move' => 16,
        'Hedge trimming' => 6,
        'Patio planter refresh' => 4,
        'Garden cleanup' => 14,
    ];

    private const COMMENTS = [
        [5, 'Arrived on time and took care of everything we agreed on.'],
        [4, 'Good work overall. We had to adjust the arrival time, but communication was clear.'],
        [5, 'They explained the work first and left the area tidy afterward.'],
        [5, 'Easy to arrange, and the final result was exactly what I needed.'],
        [3, 'The work was done, although it took a little longer than I expected.'],
        [4, 'Friendly and careful with my things. I would use the service again.'],
        [5, 'They checked the result with me before leaving, which I appreciated.'],
        [5, 'Clear communication throughout and no surprises with the price.'],
        [4, 'The main job went well. A little more notice about the arrival time would help.'],
        [5, 'Professional, punctual and happy to answer my questions.'],
    ];

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        $provider = User::where('email', config('app.demo_provider_email'))->firstOrFail();

        DB::transaction(function () use ($provider): void {
            $reviewers = [];
            $services = $provider->services()
                ->whereNull('archived_at')
                ->whereIn('title', array_keys(self::REVIEW_COUNTS))
                ->orderBy('id')
                ->get();

            foreach ($services as $service) {
                $reviewedCustomers = $service->bookings()->has('review')->pluck('customer_id')->flip();
                $count = self::REVIEW_COUNTS[$service->title];

                for ($position = 0; $position < $count; $position++) {
                    $reviewer = $reviewers[$position] ??= $this->reviewer($position);

                    if ($position === 0 && in_array($service->title, self::AWAITING_CUSTOMER_REVIEW, true)) {
                        if (! $service->bookings()->whereBelongsTo($reviewer, 'customer')->where('status', BookingStatus::Completed)->exists()) {
                            Booking::factory()->completed()->for($service)->for($reviewer, 'customer')
                                ->create(['scheduled_at' => now()->subDays(2)]);
                        }

                        continue;
                    }

                    if ($reviewedCustomers->has($reviewer->id)) {
                        continue;
                    }

                    [$rating, $comment] = self::COMMENTS[($position + $service->id) % count(self::COMMENTS)];
                    $booking = Booking::factory()->completed()
                        ->for($service)
                        ->for($reviewer, 'customer')
                        ->create(['scheduled_at' => now()->subDays($position + 2)]);

                    Review::factory()->for($booking)->create(['rating' => $rating, 'comment' => $comment]);
                }
            }
        });
    }

    private function reviewer(int $position): User
    {
        $email = $position === 0
            ? 'customer@servio.example'
            : 'demo-reviewer-'.$position.'@servio.example';

        return User::where('email', $email)->first()
            ?? User::factory()->create(['email' => $email]);
    }
}
