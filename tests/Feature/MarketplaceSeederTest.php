<?php

use App\Models\BookingStatus;
use App\Models\Category;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoMarketplaceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('local seeding creates a linked demo and can be repeated', function () {
    $this->seed();
    $this->seed();

    $this->assertDatabaseCount('categories', 6);
    $this->assertDatabaseCount('services', 18);
    $this->assertDatabaseCount('bookings', 126);
    $this->assertDatabaseCount('reviews', 122);

    $customer = User::where('email', 'customer@servio.example')->sole();
    $unreviewedServices = $customer->customerBookings()
        ->where('status', BookingStatus::Completed)
        ->doesntHave('review')
        ->with('service')
        ->get()
        ->pluck('service.title')
        ->sort()
        ->values()
        ->all();

    expect($unreviewedServices)->toBe(['Apartment cleaning', 'Patio planter refresh', 'Small apartment move']);

    $reviewCounts = Service::withCount('reviews')->get()->pluck('reviews_count');
    $reviewed = $reviewCounts->filter();
    expect($reviewed->count())->toBe(14)
        ->and($reviewed->min())->toBe(3)
        ->and($reviewed->max())->toBe(19);

    $serviceCounts = Category::withCount(['services' => fn ($query) => $query->where('is_published', true)])
        ->get()->pluck('services_count')->all();
    expect($serviceCounts)->toBe(array_fill(0, 6, 3));

    $service = Service::where('title', 'Garden cleanup')->sole();
    $review = $service->reviews()->oldest('reviews.id')->firstOrFail();

    expect($service->is_published)->toBeTrue()
        ->and($service->category->slug)->toBe('gardening')
        ->and($service->provider->is_admin)->toBeFalse()
        ->and($service->bookings()->where('status', BookingStatus::Pending)->sole()->price_cents)->toBe($service->price_cents)
        ->and($review->booking->status)->toBe(BookingStatus::Completed)
        ->and($review->booking->price_cents)->toBe($service->price_cents)
        ->and($review->booking->customer->is($service->provider))->toBeFalse();
});

test('production seeding retains categories and skips demo records', function () {
    $this->app->instance('env', 'production');

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(Category::count())->toBe(6)
        ->and(User::count())->toBe(0)
        ->and(Service::count())->toBe(0);
});

test('a standalone review factory creates a completed booking with a price snapshot', function () {
    $booking = Review::factory()->create()->booking;

    expect($booking->status)->toBe(BookingStatus::Completed)
        ->and($booking->price_cents)->toBe($booking->service->price_cents);
});

test('seeding expands a legacy demo catalogue and preserves its original listing', function () {
    $this->seed(CategorySeeder::class);
    config(['app.demo_provider_email' => 'local-provider@example.com']);
    $provider = User::factory()->create(['email' => 'local-provider@example.com']);
    $category = Category::where('slug', 'gardening')->sole();
    $listing = Service::factory()->published()->for($provider, 'provider')->for($category)->create([
        'title' => 'Garden cleanup',
        'price_cents' => 3900,
    ]);

    $this->seed();
    $this->seed();

    $this->assertDatabaseCount('services', 18);
    expect($listing->fresh()->price_cents)->toBe(3900)
        ->and($provider->services()->count())->toBe(18);
});

test('re-seeding attaches generated covers and shows their galleries', function () {
    $disk = Storage::fake('public');
    $this->withoutVite();
    $this->seed();

    $service = Service::where('title', 'Garden cleanup')->sole();
    $cover = DemoMarketplaceSeeder::imagePath('gardening', $service->title, 1);
    $secondPhoto = DemoMarketplaceSeeder::imagePath('gardening', $service->title, 2);
    $disk->put($cover, 'cover fixture');
    $disk->put($secondPhoto, 'gallery fixture');

    $this->seed();

    expect($service->refresh()->image_path)->toBe($cover);
    $this->get(route('services.show', $service))->assertOk()->assertSee($cover)->assertSee($secondPhoto);

    $service->image_path = 'services/provider-cover.jpg';
    $service->save();
    $this->seed();

    expect($service->refresh()->image_path)->toBe('services/provider-cover.jpg');
    $this->get(route('services.show', $service))->assertOk()->assertDontSee($secondPhoto);
});
