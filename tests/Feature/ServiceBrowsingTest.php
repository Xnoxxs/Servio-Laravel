<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Review;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('guests see published services and category links on the homepage', function () {
    $this->seed();
    $draft = Service::factory()->create(['title' => 'Private draft listing']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Featured services')
        ->assertSee('Garden cleanup')
        ->assertSee('€35.00')
        ->assertSee('Demo Provider')
        ->assertSee(route('services.index', ['category' => 'gardening']))
        ->assertDontSee($draft->title);
});

test('category filters retain their selection across catalogue pages', function () {
    $gardening = Category::factory()->create();
    $other = Service::factory()->published()->create(['title' => 'Outside this category']);
    $draft = Service::factory()->for($gardening)->create(['title' => 'Still a draft']);
    $oldest = Service::factory()->published()->for($gardening)->create(['title' => 'Oldest garden listing']);
    Service::factory()->count(9)->published()->for($gardening)->create();

    $this->get(route('services.index', ['category' => $gardening->slug]))
        ->assertOk()
        ->assertSee($gardening->name)
        ->assertSee(route('services.index', ['category' => $gardening->slug, 'page' => 2]))
        ->assertDontSee($other->title)
        ->assertDontSee($draft->title)
        ->assertDontSee($oldest->title);

    $this->get(route('services.index', ['category' => $gardening->slug, 'page' => 2]))
        ->assertOk()
        ->assertSee($oldest->title);
});

test('an empty category offers a way back to the full catalogue', function () {
    $category = Category::factory()->create();

    $this->get(route('services.index', ['category' => $category->slug]))
        ->assertOk()
        ->assertSee('No services found')
        ->assertSee('View all services');

    $this->get(route('services.index', ['category' => 'unknown-category']))
        ->assertNotFound();
});

test('keyword search matches titles and descriptions within the selected category', function () {
    $category = Category::factory()->create();
    $titleMatch = Service::factory()->for($category)->published()->create(['title' => 'Patio cleaning']);
    $descriptionMatch = Service::factory()->for($category)->published()->create([
        'title' => 'Outdoor tidy-up',
        'description' => 'Wash your patio and walkways.',
    ]);
    $otherCategory = Service::factory()->published()->create(['title' => 'Patio work in another category']);
    $draft = Service::factory()->for($category)->create(['title' => 'Patio draft']);

    $this->get(route('services.index', ['category' => $category->slug, 'q' => 'PATIO']))
        ->assertOk()
        ->assertSee($titleMatch->title)
        ->assertSee($descriptionMatch->title)
        ->assertDontSee($otherCategory->title)
        ->assertDontSee($draft->title);
});

test('keyword search treats wildcard characters literally', function () {
    $allergyFriendly = Service::factory()->published()->create(['title' => '100% fragrance-free cleaning']);
    $other = Service::factory()->published()->create(['title' => '100 point cleaning']);

    $this->get(route('services.index', ['q' => '100%']))
        ->assertOk()
        ->assertSee($allergyFriendly->title)
        ->assertDontSee($other->title);
});

test('a public service page shows its details and visible reviews', function () {
    $service = Service::factory()->published()->create([
        'price_cents' => 4500,
        'duration_minutes' => 75,
        'location' => 'Madrid',
        'image_path' => 'services/garden.jpg',
    ]);
    $bookings = Booking::factory()->count(2)->for($service)->completed()->create();
    $visible = Review::factory()->for($bookings[0])->create(['comment' => 'Very thorough work.']);
    $hidden = Review::factory()->for($bookings[1])->create(['comment' => 'Hidden by moderation.', 'is_hidden' => true]);

    $this->get(route('services.show', $service))
        ->assertOk()
        ->assertSee($service->title)
        ->assertSee($service->provider->name)
        ->assertSee('€45.00')
        ->assertSee('75 min')
        ->assertSee('Madrid')
        ->assertSee('services/garden.jpg')
        ->assertSee($visible->comment)
        ->assertDontSee($hidden->comment);
});

test('a draft listing cannot be opened through its public URL', function () {
    $draft = Service::factory()->create(['title' => 'Unpublished private title']);

    $this->get(route('services.show', $draft))
        ->assertNotFound()
        ->assertSee('Browse available services')
        ->assertDontSee($draft->title);
});
