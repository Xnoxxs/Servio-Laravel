<?php

use App\Models\Category;
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
