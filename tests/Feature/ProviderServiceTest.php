<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('guests must log in to reach the provider dashboard or create services', function () {
    $this->get(route('provider.dashboard'))->assertRedirect(route('login'));
    $this->post(route('provider.services.store'))->assertRedirect(route('login'));
});

test('members can create a draft and ownership comes from their session', function () {
    $provider = User::factory()->create();
    $other = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($provider)->get(route('provider.services.create'))->assertOk();
    $this->post(route('provider.services.store'), [
        'title' => 'Hedge trimming',
        'category_id' => $category->id,
        'description' => 'Trim and tidy hedges.',
        'price' => '35,90',
        'duration_minutes' => 75,
        'location' => 'Madrid',
        'provider_id' => $other->id,
        'is_published' => true,
    ])->assertRedirect(route('provider.dashboard'));

    $this->assertDatabaseHas('services', [
        'title' => 'Hedge trimming',
        'provider_id' => $provider->id,
        'category_id' => $category->id,
        'price_cents' => 3590,
        'duration_minutes' => 75,
        'location' => 'Madrid',
        'is_published' => false,
    ]);
});

test('provider forms reject invalid listing details', function () {
    $this->actingAs(User::factory()->create())->post(route('provider.services.store'), [
        'category_id' => 999,
        'price' => '10.005',
        'duration_minutes' => 0,
    ])->assertSessionHasErrors(['title', 'category_id', 'description', 'price', 'duration_minutes', 'location']);

    $this->assertDatabaseCount('services', 0);
});

test('providers see and edit their own listings', function () {
    $service = Service::factory()->create(['title' => 'My service']);
    $otherService = Service::factory()->create(['title' => 'Another private service']);
    $category = Category::factory()->create();

    $this->actingAs($service->provider)->get(route('provider.dashboard'))
        ->assertOk()->assertSee($service->title)->assertDontSee($otherService->title);
    $this->get(route('provider.services.edit', $service))->assertOk()->assertSee($service->title);
    $this->put(route('provider.services.update', $service), [
        'title' => 'Updated service',
        'category_id' => $category->id,
        'description' => 'New description.',
        'price' => '19.9',
        'duration_minutes' => 45,
        'location' => 'Toledo',
        'provider_id' => $otherService->provider_id,
    ])->assertRedirect(route('provider.dashboard'));

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'title' => 'Updated service',
        'category_id' => $category->id,
        'price_cents' => 1990,
        'duration_minutes' => 45,
        'location' => 'Toledo',
        'provider_id' => $service->provider_id,
    ]);
});

test('providers can publish and deactivate without deleting a listing', function () {
    $service = Service::factory()->create();

    $this->actingAs($service->provider)
        ->patch(route('provider.services.publication', $service), ['is_published' => '1'])
        ->assertRedirect(route('provider.dashboard'));
    $this->get(route('services.show', $service))->assertOk();

    $this->patch(route('provider.services.publication', $service), ['is_published' => '0'])
        ->assertRedirect(route('provider.dashboard'));
    $this->get(route('services.show', $service))->assertNotFound();
    $this->get(route('provider.services.edit', $service))->assertOk();
});

test('deleting a listing removes it from both views and preserves the booking history', function () {
    $booking = Booking::factory()->create();
    $service = $booking->service;

    $this->actingAs($service->provider)->get(route('provider.services.edit', $service))
        ->assertOk()->assertSee('Confirm archive')->assertSee(route('provider.services.destroy', $service));

    $this->actingAs($service->provider)->delete(route('provider.services.destroy', $service))
        ->assertRedirect(route('provider.dashboard'));

    $this->get(route('provider.dashboard'))->assertOk()->assertDontSee($service->title);
    $this->get(route('services.show', $service))->assertNotFound();
    $this->patch(route('provider.services.publication', $service), ['is_published' => '1'])->assertForbidden();
    expect($service->fresh()->archived_at)->not->toBeNull()
        ->and($booking->fresh()->service->is($service))->toBeTrue();
});

test('other members and administrators cannot use owner modification routes', function (bool $isAdmin) {
    $service = Service::factory()->published()->create();
    $other = User::factory()->create(['is_admin' => $isAdmin]);

    $this->actingAs($other)->get(route('provider.services.edit', $service))->assertForbidden();
    $this->put(route('provider.services.update', $service), ['title' => 'Unauthorized'])->assertForbidden();
    $this->patch(route('provider.services.publication', $service), ['is_published' => '0'])->assertForbidden();
    $this->delete(route('provider.services.destroy', $service))->assertForbidden();

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'title' => $service->title,
        'is_published' => true,
        'archived_at' => null,
    ]);
})->with([
    'member' => [false],
    'administrator' => [true],
]);

test('providers can upload, replace and remove a service cover', function () {
    $disk = Storage::fake('public');
    $provider = User::factory()->create();
    $category = Category::factory()->create();
    $details = [
        'title' => 'Window cleaning',
        'category_id' => $category->id,
        'description' => 'Clean interior windows.',
        'price' => '40',
        'duration_minutes' => 60,
        'location' => 'Madrid',
    ];

    $this->actingAs($provider)->post(route('provider.services.store'), $details + [
        'image' => UploadedFile::fake()->image('cover.jpg'),
    ])->assertRedirect(route('provider.dashboard'));

    $service = $provider->services()->sole();
    $original = $service->image_path;
    expect($original)->toStartWith('services/');
    $disk->assertExists($original);

    $this->get(route('provider.services.edit', $service))->assertOk()->assertSee($original);
    $this->put(route('provider.services.update', $service), $details + [
        'image' => UploadedFile::fake()->image('replacement.png'),
    ])->assertRedirect(route('provider.dashboard'));

    $replacement = $service->refresh()->image_path;
    expect($replacement)->not->toBe($original);
    $disk->assertExists($replacement);
    $disk->assertMissing($original);

    $this->put(route('provider.services.update', $service), $details + ['remove_image' => '1'])
        ->assertRedirect(route('provider.dashboard'));

    expect($service->refresh()->image_path)->toBeNull();
    $disk->assertMissing($replacement);
});

test('an invalid upload leaves the existing cover intact', function () {
    $disk = Storage::fake('public');
    $disk->put('services/cover.jpg', 'stored cover');
    $service = Service::factory()->create(['image_path' => 'services/cover.jpg']);

    $this->actingAs($service->provider)->put(route('provider.services.update', $service), [
        'title' => $service->title,
        'category_id' => $service->category_id,
        'description' => $service->description,
        'price' => '40',
        'duration_minutes' => 60,
        'location' => 'Madrid',
        'image' => UploadedFile::fake()->create('cover.svg', 12, 'image/svg+xml'),
    ])->assertSessionHasErrors('image');

    expect($service->refresh()->image_path)->toBe('services/cover.jpg')
        ->and($disk->allFiles())->toBe(['services/cover.jpg']);
});

test('archiving removes the cover file and retains the service for its bookings', function () {
    $disk = Storage::fake('public');
    $disk->put('services/cover.png', 'stored cover');
    $service = Service::factory()->published()->create(['image_path' => 'services/cover.png']);
    $booking = Booking::factory()->for($service)->create();

    $this->actingAs($service->provider)->delete(route('provider.services.destroy', $service))
        ->assertRedirect(route('provider.dashboard'));

    $disk->assertMissing('services/cover.png');
    expect($booking->fresh()->service->image_path)->toBeNull()
        ->and($booking->service->archived_at)->not->toBeNull();
});
