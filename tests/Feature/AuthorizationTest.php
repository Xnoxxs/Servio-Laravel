<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

test('the admin route requires authentication and the administrator flag', function () {
    $member = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->withoutVite();

    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->actingAs($member)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Administration');
});

test('listing visibility and mutation respect ownership and explicit moderation rights', function () {
    $service = Service::factory()->create();
    $other = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    expect(Gate::allows('view', $service))->toBeFalse()
        ->and(Gate::allows('create', Service::class))->toBeFalse()
        ->and($service->provider->can('view', $service))->toBeTrue()
        ->and($service->provider->can('update', $service))->toBeTrue()
        ->and($service->provider->can('delete', $service))->toBeTrue()
        ->and($other->can('update', $service))->toBeFalse()
        ->and($other->can('delete', $service))->toBeFalse()
        ->and($admin->can('update', $service))->toBeFalse()
        ->and($admin->can('delete', $service))->toBeFalse()
        ->and($other->can('unpublish', $service))->toBeFalse()
        ->and($admin->can('unpublish', $service))->toBeTrue();

    $service->is_published = true;
    $service->save();

    expect(Gate::allows('view', $service))->toBeTrue();
});

test('members can offer listings and request published services including their own', function () {
    $service = Service::factory()->published()->create();
    $member = User::factory()->create();
    $draft = Service::factory()->create();

    expect($member->can('create', Service::class))->toBeTrue()
        ->and($member->can('create', [Booking::class, $service]))->toBeTrue()
        ->and($service->provider->can('create', [Booking::class, $service]))->toBeTrue()
        ->and($member->can('create', [Booking::class, $draft]))->toBeFalse()
        ->and(Gate::allows('create', [Booking::class, $service]))->toBeFalse();
});

test('private booking abilities are limited to the relevant customer or provider', function () {
    $booking = Booking::factory()->create();
    $provider = $booking->service->provider;
    $customer = $booking->customer;
    $other = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    expect(Gate::allows('view', $booking))->toBeFalse()
        ->and($customer->can('view', $booking))->toBeTrue()
        ->and($customer->can('cancel', $booking))->toBeTrue()
        ->and($customer->can('manage', $booking))->toBeFalse()
        ->and($provider->can('view', $booking))->toBeFalse()
        ->and($provider->can('cancel', $booking))->toBeFalse()
        ->and($provider->can('manage', $booking))->toBeTrue()
        ->and($other->can('view', $booking))->toBeFalse()
        ->and($other->can('cancel', $booking))->toBeFalse()
        ->and($other->can('manage', $booking))->toBeFalse()
        ->and($admin->can('view', $booking))->toBeFalse()
        ->and($admin->can('cancel', $booking))->toBeFalse()
        ->and($admin->can('manage', $booking))->toBeFalse();
});

test('ownership and booking state cannot be assigned from mass input', function () {
    $booking = Booking::factory()->create();
    $service = $booking->service;
    $other = User::factory()->create();
    $customerId = $booking->customer_id;
    $providerId = $service->provider_id;

    $booking->fill(['customer_id' => $other->id, 'service_id' => 0, 'status' => 'completed'])->save();
    $service->fill(['provider_id' => $other->id])->save();

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'customer_id' => $customerId,
        'service_id' => $service->id,
        'status' => 'pending',
    ]);
    $this->assertDatabaseHas('services', ['id' => $service->id, 'provider_id' => $providerId]);
});
