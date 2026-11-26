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

test('a customer requests a future appointment and receives a private price and duration snapshot', function () {
    $service = Service::factory()->published()->create(['price_cents' => 2500, 'duration_minutes' => 75]);
    $customer = User::factory()->create();

    $this->actingAs($customer)->get(route('bookings.create', $service))->assertOk();
    $response = $this->post(route('bookings.store', $service), [
        'scheduled_at' => '2026-11-24T13:15',
        'customer_id' => $service->provider_id,
        'price_cents' => 1,
        'duration_minutes' => 5,
        'status' => 'accepted',
    ]);
    $booking = Booking::sole();
    $response->assertRedirect(route('bookings.show', $booking));
    $service->update(['price_cents' => 4500, 'duration_minutes' => 120]);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'scheduled_at' => '2026-11-24 12:15:00',
        'price_cents' => 2500,
        'duration_minutes' => 75,
        'currency' => 'EUR',
        'status' => BookingStatus::Pending->value,
    ]);

    $this->get(route('bookings.index'))->assertOk()->assertSee($service->title)->assertSee('Pending');
    $this->get(route('bookings.show', $booking))->assertOk()->assertSee('€25.00')->assertSee('75 min')->assertSee('13:15');
});

test('guests must sign in and customers can only see their own bookings', function () {
    $booking = Booking::factory()->create(['price_cents' => 3913]);
    $customer = User::factory()->create();

    $this->get(route('bookings.create', $booking->service))->assertRedirect(route('login'));
    $this->post(route('bookings.store', $booking->service))->assertRedirect(route('login'));
    $this->get(route('bookings.index'))->assertRedirect(route('login'));

    $this->actingAs($customer)->get(route('bookings.index'))->assertOk()->assertDontSee($booking->service->title);
    $this->get(route('bookings.show', $booking))->assertForbidden();
    $this->actingAs($booking->service->provider)->get(route('bookings.show', $booking))->assertForbidden();
});

test('a provider can book their own published service', function () {
    $customer = User::factory()->create();
    $ownService = Service::factory()->published()->for($customer, 'provider')->create();

    $this->actingAs($customer);
    $this->get(route('services.show', $ownService))->assertOk()->assertSee('Request a booking');
    $this->get(route('bookings.create', $ownService))->assertOk();
    $response = $this->post(route('bookings.store', $ownService), ['scheduled_at' => '2026-11-24T13:15']);

    $booking = Booking::sole();
    $response->assertRedirect(route('bookings.show', $booking));
    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'service_id' => $ownService->id,
        'customer_id' => $customer->id,
        'status' => BookingStatus::Pending->value,
    ]);
});

test('booking requests reject unpublished services', function () {
    $draft = Service::factory()->create();

    $this->actingAs(User::factory()->create());
    $this->get(route('bookings.create', $draft))->assertForbidden();
    $this->post(route('bookings.store', $draft), ['scheduled_at' => '2026-11-24T13:15'])->assertForbidden();

    $this->assertDatabaseCount('bookings', 0);
});

test('booking requests reject invalid local appointment times', function (string $localTime, string $error) {
    $service = Service::factory()->published()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('bookings.store', $service), ['scheduled_at' => $localTime])
        ->assertSessionHasErrors(['scheduled_at' => $error]);

    $this->assertDatabaseCount('bookings', 0);
})->with([
    'past' => ['2026-11-22T12:00', 'Choose a date and time in the future.'],
    'spring gap' => ['2027-03-28T02:30', 'Choose an unambiguous time in Europe/Madrid outside the daylight-saving change.'],
    'autumn overlap' => ['2027-10-31T02:30', 'Choose an unambiguous time in Europe/Madrid outside the daylight-saving change.'],
]);
