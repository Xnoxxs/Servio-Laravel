<?php

use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('a customer can review their completed booking and the review appears on the service', function () {
    $booking = Booking::factory()->completed()->create();

    $this->actingAs($booking->customer)->get(route('bookings.show', $booking))
        ->assertOk()->assertSee('Leave a review');
    $this->post(route('bookings.reviews.store', $booking), [
        'rating' => 4,
        'comment' => 'The provider arrived right on time.',
        'booking_id' => 0,
        'is_hidden' => true,
    ])->assertRedirect(route('bookings.show', $booking));

    $this->assertDatabaseHas('reviews', [
        'booking_id' => $booking->id,
        'rating' => 4,
        'comment' => 'The provider arrived right on time.',
        'is_hidden' => false,
    ]);

    $this->get(route('bookings.show', $booking))->assertOk()->assertSee('Your review')->assertDontSee('Post review');
    $this->get(route('services.show', $booking->service))
        ->assertOk()->assertSee($booking->customer->name)->assertSee('4 out of 5 stars')->assertSee('The provider arrived right on time.');
});

test('a rating can be submitted without a comment and a second submission keeps the original', function () {
    $booking = Booking::factory()->completed()->create();

    $this->actingAs($booking->customer)->post(route('bookings.reviews.store', $booking), ['rating' => 5])
        ->assertRedirect(route('bookings.show', $booking));
    $this->post(route('bookings.reviews.store', $booking), ['rating' => 1])
        ->assertRedirect(route('bookings.show', $booking))
        ->assertSessionHasErrors(['booking' => 'You already reviewed this booking.']);

    $review = Review::sole();
    expect($review->rating)->toBe(5)->and($review->comment)->toBeNull();
});

test('guests and anyone other than the booking customer cannot submit a review', function () {
    $booking = Booking::factory()->completed()->create();
    $url = route('bookings.reviews.store', $booking);

    $this->post($url, ['rating' => 5])->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->post($url, ['rating' => 5])->assertForbidden();
    $this->actingAs($booking->service->provider)->post($url, ['rating' => 5])->assertForbidden();

    $this->assertDatabaseCount('reviews', 0);
});

test('own services cannot be reviewed even if a legacy booking exists', function () {
    $booking = Booking::factory()->completed()->create();
    $booking->customer()->associate($booking->service->provider);
    $booking->save();

    $this->actingAs($booking->customer)->post(route('bookings.reviews.store', $booking), ['rating' => 5])
        ->assertForbidden();
    $this->assertDatabaseCount('reviews', 0);
});

test('customers cannot review a booking that has not been completed', function (BookingStatus $status) {
    $booking = Booking::factory()->create(['status' => $status]);

    $this->actingAs($booking->customer)->get(route('bookings.show', $booking))
        ->assertOk()->assertDontSee('Leave a review');
    $this->post(route('bookings.reviews.store', $booking), ['rating' => 5])->assertForbidden();

    $this->assertDatabaseCount('reviews', 0);
})->with([BookingStatus::Pending, BookingStatus::Accepted, BookingStatus::Cancelled, BookingStatus::Rejected]);

test('ratings must be whole numbers from one to five and comments cannot exceed 2000 characters', function () {
    $booking = Booking::factory()->completed()->create();

    $this->actingAs($booking->customer)->post(route('bookings.reviews.store', $booking), [
        'rating' => 6,
        'comment' => str_repeat('a', 2001),
    ])->assertSessionHasErrors(['rating', 'comment']);
    $this->post(route('bookings.reviews.store', $booking), ['rating' => 4.5])->assertSessionHasErrors('rating');

    $this->assertDatabaseCount('reviews', 0);
});
