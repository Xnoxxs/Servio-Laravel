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
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        $providerEmail = 'provider@servio.example';
        $customerEmail = 'customer@servio.example';

        DB::transaction(function () use ($providerEmail, $customerEmail): void {
            if (User::whereIn('email', [$providerEmail, $customerEmail])->exists()) {
                return;
            }

            $provider = User::factory()->create(['name' => 'Demo Provider', 'email' => $providerEmail]);
            $customer = User::factory()->create(['name' => 'Demo Customer', 'email' => $customerEmail]);
            $category = Category::where('slug', 'gardening')->firstOrFail();

            $service = Service::factory()->published()
                ->for($provider, 'provider')
                ->for($category)
                ->create([
                    'title' => 'Garden cleanup',
                    'description' => 'Clear leaves, trim hedges and tidy garden paths.',
                    'price_cents' => 3500,
                ]);

            Booking::factory()->for($service)->for($customer, 'customer')->create();

            $completedBooking = Booking::factory()->completed()
                ->for($service)
                ->for($customer, 'customer')
                ->create();

            Review::factory()->for($completedBooking)->create([
                'rating' => 5,
                'comment' => 'The garden paths were left clean and tidy.',
            ]);
        });
    }
}
