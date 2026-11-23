<?php

use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(now()->setDate(2026, 11, 23)->setTime(10, 0));
});

test('customer and provider see a request progress from pending to accepted to completed', function () {
    $service = Service::factory()->published()->create();
    $customer = User::factory()->create();

    $this->actingAs($customer)->post(route('bookings.store', $service), [
        'scheduled_at' => '2026-11-24T13:00',
    ])->assertRedirect();
    $booking = Booking::sole();

    $this->actingAs($service->provider)->get(route('provider.bookings.index'))
        ->assertOk()->assertSee($customer->name)->assertSee('Pending');
    $this->get(route('provider.bookings.show', $booking))->assertOk()->assertSee('Accept');
    $this->patch(route('provider.bookings.accept', $booking))->assertRedirect(route('provider.bookings.show', $booking));
    $this->get(route('provider.bookings.show', $booking))->assertOk()->assertSee('Accepted');

    $this->actingAs($customer)->get(route('bookings.show', $booking))->assertOk()->assertSee('Accepted');

    $this->actingAs($service->provider)->patch(route('provider.bookings.complete', $booking))
        ->assertSessionHasErrors('booking');
    expect($booking->refresh()->status)->toBe(BookingStatus::Accepted);

    $this->travelTo($booking->scheduled_at);
    $this->patch(route('provider.bookings.complete', $booking))->assertRedirect(route('provider.bookings.show', $booking));
    $this->actingAs($customer)->get(route('bookings.show', $booking))->assertOk()->assertSee('Completed');
    expect($booking->refresh()->status)->toBe(BookingStatus::Completed);
});

test('providers can reject pending requests and rejected bookings stay terminal', function () {
    $booking = Booking::factory()->create();

    $this->actingAs($booking->service->provider)
        ->patch(route('provider.bookings.reject', $booking))->assertRedirect(route('provider.bookings.show', $booking));
    $this->patch(route('provider.bookings.accept', $booking))->assertSessionHasErrors('booking');
    $this->actingAs($booking->customer)->get(route('bookings.index'))->assertOk()->assertSee('Rejected');
    $this->patch(route('bookings.cancel', $booking))->assertSessionHasErrors('booking');
    expect($booking->refresh()->status)->toBe(BookingStatus::Rejected);
});

test('customers can cancel pending or accepted future bookings', function (BookingStatus $status) {
    $booking = Booking::factory()->create(['status' => $status]);

    $this->actingAs($booking->customer)
        ->patch(route('bookings.cancel', $booking))->assertRedirect(route('bookings.show', $booking));
    $this->actingAs($booking->service->provider)->get(route('provider.bookings.show', $booking))
        ->assertOk()->assertSee('Cancelled');
    expect($booking->refresh()->status)->toBe(BookingStatus::Cancelled);
})->with([BookingStatus::Pending, BookingStatus::Accepted]);

test('appointments at or after their start can no longer be accepted or cancelled', function () {
    $booking = Booking::factory()->create(['scheduled_at' => now()]);

    $this->actingAs($booking->customer)->patch(route('bookings.cancel', $booking))->assertSessionHasErrors('booking');
    $this->actingAs($booking->service->provider)->patch(route('provider.bookings.accept', $booking))->assertSessionHasErrors('booking');
    expect($booking->refresh()->status)->toBe(BookingStatus::Pending);
});

test('only the booking participants can act in the correct role', function () {
    $booking = Booking::factory()->create();
    $otherProvider = Service::factory()->create()->provider;

    $this->get(route('provider.bookings.index'))->assertRedirect(route('login'));
    $this->actingAs($booking->customer)->patch(route('provider.bookings.accept', $booking))->assertForbidden();
    $this->actingAs($booking->service->provider)->patch(route('bookings.cancel', $booking))->assertForbidden();

    $this->actingAs($otherProvider)->get(route('provider.bookings.index'))->assertOk()->assertDontSee($booking->service->title);
    $this->get(route('provider.bookings.show', $booking))->assertForbidden();

    foreach (['accept', 'reject', 'complete'] as $action) {
        $this->patch(route('provider.bookings.'.$action, $booking))->assertForbidden();
    }

    $this->patch(route('bookings.cancel', $booking))->assertForbidden();
    expect($booking->refresh()->status)->toBe(BookingStatus::Pending);
});

test('same-service overlaps are rejected using saved durations and adjacent appointments are allowed', function () {
    $service = Service::factory()->published()->create(['duration_minutes' => 60]);
    $start = now()->addDay();
    $first = Booking::factory()->for($service)->create(['scheduled_at' => $start]);
    $overlap = Booking::factory()->for($service)->create(['scheduled_at' => $start->copy()->addMinutes(30)]);
    $adjacent = Booking::factory()->for($service)->create(['scheduled_at' => $start->copy()->addHour()]);
    $otherService = Service::factory()->published()->for($service->provider, 'provider')->create();
    $otherBooking = Booking::factory()->for($otherService)->create(['scheduled_at' => $start]);
    $service->update(['duration_minutes' => 15]);

    $this->actingAs($service->provider)->patch(route('provider.bookings.accept', $first))->assertRedirect();
    $this->patch(route('provider.bookings.accept', $overlap))
        ->assertSessionHasErrors(['booking' => 'An accepted appointment for this service overlaps the requested time.']);
    $this->patch(route('provider.bookings.accept', $adjacent))->assertRedirect();
    $this->patch(route('provider.bookings.accept', $otherBooking))->assertRedirect();

    expect($first->refresh()->status)->toBe(BookingStatus::Accepted)
        ->and($overlap->refresh()->status)->toBe(BookingStatus::Pending)
        ->and($adjacent->refresh()->status)->toBe(BookingStatus::Accepted)
        ->and($otherBooking->refresh()->status)->toBe(BookingStatus::Accepted);
});

test('cancelling an accepted booking releases its slot', function () {
    $first = Booking::factory()->create(['status' => BookingStatus::Accepted]);
    $replacement = Booking::factory()->for($first->service)->create(['scheduled_at' => $first->scheduled_at]);

    $this->actingAs($first->customer)->patch(route('bookings.cancel', $first))->assertRedirect();
    $this->actingAs($first->service->provider)->patch(route('provider.bookings.accept', $replacement))->assertRedirect();
    expect($replacement->refresh()->status)->toBe(BookingStatus::Accepted);
});
