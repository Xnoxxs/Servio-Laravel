<?php

use App\Models\BookingStatus;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a booking links its customer, provider, service, category and review', function () {
    $provider = User::factory()->create();
    $customer = User::factory()->create();
    $category = Category::create(['name' => 'Gardening', 'slug' => 'gardening']);
    $service = $provider->services()->make([
        'title' => 'Garden cleanup',
        'description' => 'Clear leaves and trim hedges.',
        'price_cents' => 3500,
    ]);
    $service->category()->associate($category);
    $service->save();

    $scheduledAt = now()->addDay()->startOfMinute();
    $booking = $service->bookings()->make(['scheduled_at' => $scheduledAt]);
    $booking->customer()->associate($customer);
    $booking->price_cents = $service->price_cents;
    $booking->currency = 'EUR';
    $booking->save();
    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Pending);
    expect($booking->scheduled_at->equalTo($scheduledAt))->toBeTrue();

    $booking->status = BookingStatus::Completed;
    $booking->save();
    $review = $booking->review()->create(['rating' => 5]);

    expect($booking->fresh()->status)->toBe(BookingStatus::Completed);
    expect($booking->service->is($service))->toBeTrue();
    expect($booking->customer->is($customer))->toBeTrue();
    expect($booking->review->is($review))->toBeTrue();
    expect($review->booking->is($booking))->toBeTrue();
    expect($service->provider->is($provider))->toBeTrue();
    expect($service->category->is($category))->toBeTrue();
    expect($category->services->modelKeys())->toBe([$service->id]);
    expect($provider->services->modelKeys())->toBe([$service->id]);
    expect($provider->providerBookings->modelKeys())->toBe([$booking->id]);
    expect($provider->customerBookings)->toBeEmpty();
    expect($customer->customerBookings->modelKeys())->toBe([$booking->id]);
    expect($customer->providerBookings)->toBeEmpty();
    expect($service->bookings->modelKeys())->toBe([$booking->id]);
    expect($service->reviews->modelKeys())->toBe([$review->id]);

    expect($provider->fresh()->is_admin)->toBeFalse();
    expect($service->fresh()->is_published)->toBeFalse();
    expect($review->fresh()->is_hidden)->toBeFalse();
    expect($review->fresh()->rating)->toBe(5);
    expect($booking->fresh()->price_cents)->toBe(3500);
});
