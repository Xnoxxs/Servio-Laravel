<?php

use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingStatusNotification;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('new requests notify the provider and link to their booking view', function () {
    Notification::fake();
    $service = Service::factory()->published()->create();
    $customer = User::factory()->create();

    $this->actingAs($customer)->post(route('bookings.store', $service), [
        'scheduled_at' => now(Booking::APPOINTMENT_TIMEZONE)->addDay()->format('Y-m-d\TH:i'),
    ])->assertRedirect();

    $booking = Booking::sole();
    Notification::assertSentTo($service->provider, BookingStatusNotification::class, function ($notification, $channels) use ($booking, $service) {
        $mail = $notification->toMail($service->provider);

        return $notification->status === BookingStatus::Pending
            && $channels === ['mail']
            && $mail->actionUrl === route('provider.bookings.show', $booking)
            && $mail->subject === 'New booking request · '.$service->title;
    });
    Notification::assertCount(1);
});

test('booking transitions notify only the other party', function (BookingStatus $from, BookingStatus $to, string $endpoint, bool $providerReceives) {
    Notification::fake();
    $booking = Booking::factory()->create([
        'status' => $from,
        'scheduled_at' => $to === BookingStatus::Completed ? now()->subHour() : now()->addDay(),
    ]);
    $provider = $booking->service->provider;
    $customer = $booking->customer;
    $recipient = $providerReceives ? $provider : $customer;
    $route = $providerReceives ? 'provider.bookings.show' : 'bookings.show';

    $this->actingAs($providerReceives ? $customer : $provider)->patch(route($endpoint, $booking))->assertRedirect();

    Notification::assertSentTo($recipient, BookingStatusNotification::class, function ($notification) use ($to, $booking, $recipient, $route) {
        return $notification->status === $to
            && $notification->toMail($recipient)->actionUrl === route($route, $booking);
    });
    Notification::assertCount(1);
})->with([
    'accepted' => [BookingStatus::Pending, BookingStatus::Accepted, 'provider.bookings.accept', false],
    'rejected' => [BookingStatus::Pending, BookingStatus::Rejected, 'provider.bookings.reject', false],
    'cancelled' => [BookingStatus::Accepted, BookingStatus::Cancelled, 'bookings.cancel', true],
    'completed' => [BookingStatus::Accepted, BookingStatus::Completed, 'provider.bookings.complete', false],
]);

test('unauthorized or invalid transitions send no notification', function () {
    Notification::fake();
    $booking = Booking::factory()->create(['status' => BookingStatus::Rejected]);

    $this->actingAs($booking->customer)->patch(route('provider.bookings.accept', $booking))->assertForbidden();
    $this->actingAs($booking->service->provider)->patch(route('provider.bookings.accept', $booking))->assertSessionHasErrors('booking');

    Notification::assertNothingSent();
});

test('delivery uses Laravel’s queued notification job', function () {
    Queue::fake([SendQueuedNotifications::class]);
    $booking = Booking::factory()->create();

    $this->actingAs($booking->service->provider)->patch(route('provider.bookings.accept', $booking))->assertRedirect();

    Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job) => $job->notification instanceof BookingStatusNotification
        && $job->notification->status === BookingStatus::Accepted
        && $job->channels === ['mail']);
});

test('rolling back an enclosing transaction discards its notification', function () {
    Notification::fake();
    $booking = Booking::factory()->create();

    DB::beginTransaction();
    try {
        $booking->transitionTo(BookingStatus::Accepted);
        Notification::assertNothingSent();
    } finally {
        DB::rollBack();
    }

    expect($booking->refresh()->status)->toBe(BookingStatus::Pending);
    Notification::assertNothingSent();
});

test('notification dispatch failures do not undo booking creation or transitions', function () {
    $this->mock(Dispatcher::class)->shouldReceive('send')->twice()->andThrow(new RuntimeException('Queue unavailable'));
    $service = Service::factory()->published()->create();

    $this->actingAs(User::factory()->create())->post(route('bookings.store', $service), [
        'scheduled_at' => now(Booking::APPOINTMENT_TIMEZONE)->addDay()->format('Y-m-d\TH:i'),
    ])->assertRedirect();
    $booking = Booking::sole();
    expect($booking->status)->toBe(BookingStatus::Pending);

    $this->actingAs($service->provider)->patch(route('provider.bookings.accept', $booking))->assertRedirect();
    expect($booking->refresh()->status)->toBe(BookingStatus::Accepted);
});
