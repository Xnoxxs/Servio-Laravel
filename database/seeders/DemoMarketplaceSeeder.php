<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoMarketplaceSeeder extends Seeder
{
    private const LISTINGS = [
        'car-washing' => [
            ['Exterior car wash', 'Hand wash the bodywork, wheels and exterior glass.', 2200, 35, 'Madrid'],
            ['Interior car detailing', 'Vacuum seats and mats and clean interior surfaces.', 4800, 90, 'Alcobendas'],
            ['Full car clean', 'Wash the exterior and vacuum and wipe down the interior.', 6500, 120, 'Getafe'],
        ],
        'house-cleaning' => [
            ['Apartment cleaning', 'Dust, vacuum and mop a one-bedroom apartment.', 5500, 120, 'Madrid'],
            ['Kitchen deep clean', 'Degrease the hob, wipe cupboard fronts and scrub worktops.', 7000, 150, 'Leganés'],
            ['Move-out cleaning', 'Clean an empty two-bedroom apartment before handover.', 12500, 240, 'Getafe'],
        ],
        'plumbing' => [
            ['Dripping tap repair', 'Inspect the tap and replace a worn washer or seal where suitable.', 4500, 45, 'Madrid'],
            ['Slow sink drain clearing', 'Clear a local blockage and test the sink for proper drainage.', 5200, 60, 'Alcorcón'],
            ['Toilet flush repair', 'Diagnose a running cistern and repair the flush mechanism.', 6500, 75, 'Leganés'],
        ],
        'electrical-work' => [
            ['Ceiling light installation', 'Fit a customer-supplied light at an existing ceiling connection.', 4800, 45, 'Madrid'],
            ['Wall socket replacement', 'Replace a damaged socket and test the existing circuit.', 4200, 40, 'Getafe'],
            ['Doorbell fault check', 'Check the doorbell, chime and supply for common faults.', 5500, 60, 'Alcobendas'],
        ],
        'moving' => [
            ['Single-item furniture move', 'Move one large item between addresses in the local area.', 6500, 60, 'Madrid'],
            ['Packing assistance', 'Help pack and label household items using your packing supplies.', 7500, 120, 'Alcorcón'],
            ['Small apartment move', 'Load, transport and unload furniture and boxes for a studio move.', 16500, 180, 'Madrid'],
        ],
        'gardening' => [
            ['Hedge trimming', 'Shape a small hedge and collect the clippings.', 4200, 60, 'Alcobendas'],
            ['Patio planter refresh', 'Repot existing plants and tidy up patio containers.', 4900, 75, 'Madrid'],
            ['Garden cleanup', 'Clear leaves, trim hedges and tidy garden paths.', 3500, 60, 'Madrid'],
        ],
    ];

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        $providerEmail = 'provider@servio.example';
        $customerEmail = 'customer@servio.example';

        DB::transaction(function () use ($providerEmail, $customerEmail): void {
            $provider = User::where('email', $providerEmail)->first()
                ?? User::factory()->create(['name' => 'Demo Provider', 'email' => $providerEmail]);

            foreach (self::LISTINGS as $slug => $listings) {
                $category = Category::where('slug', $slug)->firstOrFail();

                foreach ($listings as [$title, $description, $priceCents, $durationMinutes, $location]) {
                    $service = $provider->services()->whereBelongsTo($category)->firstOrNew(['title' => $title]);

                    if ($service->exists) {
                        continue;
                    }

                    $service->fill([
                        'description' => $description,
                        'price_cents' => $priceCents,
                        'duration_minutes' => $durationMinutes,
                        'location' => $location,
                    ]);
                    $service->category()->associate($category);
                    $service->is_published = true;
                    $service->save();

                    if ($title === 'Garden cleanup') {
                        $customer = User::where('email', $customerEmail)->first()
                            ?? User::factory()->create(['name' => 'Demo Customer', 'email' => $customerEmail]);

                        $this->seedBookingHistory($service, $customer);
                    }
                }
            }
        });
    }

    private function seedBookingHistory(Service $service, User $customer): void
    {
        Booking::factory()->for($service)->for($customer, 'customer')->create();

        $completedBooking = Booking::factory()->completed()
            ->for($service)
            ->for($customer, 'customer')
            ->create();

        Review::factory()->for($completedBooking)->create([
            'rating' => 5,
            'comment' => 'The garden paths were left clean and tidy.',
        ]);
    }
}
