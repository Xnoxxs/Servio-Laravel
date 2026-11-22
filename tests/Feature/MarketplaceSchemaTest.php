<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('marketplace constraints protect booking and review history', function (string $violation, string $constraint) {
    $provider = User::factory()->create();
    $customer = User::factory()->create();
    $categoryId = DB::table('categories')->insertGetId(['name' => 'Gardening', 'slug' => 'gardening']);
    $service = [
        'category_id' => $categoryId,
        'provider_id' => $provider->id,
        'title' => 'Garden cleanup',
        'description' => 'Clear leaves and trim hedges.',
        'price_cents' => 3500,
    ];
    $serviceId = DB::table('services')->insertGetId($service);
    $booking = [
        'service_id' => $serviceId,
        'customer_id' => $customer->id,
        'scheduled_at' => now()->addDay(),
        'price_cents' => 3500,
        'currency' => 'EUR',
    ];
    $bookingId = DB::table('bookings')->insertGetId($booking);
    $reviewId = DB::table('reviews')->insertGetId(['booking_id' => $bookingId, 'rating' => 5]);

    $this->assertDatabaseHas('users', ['id' => $provider->id, 'is_admin' => false]);
    $this->assertDatabaseHas('services', ['id' => $serviceId, 'is_published' => false]);
    $this->assertDatabaseHas('bookings', ['id' => $bookingId, 'status' => 'pending']);
    $this->assertDatabaseHas('reviews', ['id' => $reviewId, 'is_hidden' => false]);

    $attempt = match ($violation) {
        'duplicate slug' => fn () => DB::table('categories')->insert(['name' => 'Other', 'slug' => 'gardening']),
        'duplicate review' => fn () => DB::table('reviews')->insert(['booking_id' => $bookingId, 'rating' => 4]),
        'invalid rating' => fn () => DB::table('reviews')->where('id', $reviewId)->update(['rating' => 6]),
        'negative listing price' => fn () => DB::table('services')->insert(array_replace($service, ['price_cents' => -1])),
        'negative booking price' => fn () => DB::table('bookings')->insert(array_replace($booking, ['price_cents' => -1])),
        'delete referenced category' => fn () => DB::table('categories')->where('id', $categoryId)->delete(),
        'delete referenced provider' => fn () => DB::table('users')->where('id', $provider->id)->delete(),
        'delete referenced customer' => fn () => DB::table('users')->where('id', $customer->id)->delete(),
        'delete booked service' => fn () => DB::table('services')->where('id', $serviceId)->delete(),
        'delete reviewed booking' => fn () => DB::table('bookings')->where('id', $bookingId)->delete(),
    };

    expect($attempt)->toThrow(QueryException::class, $constraint);
})->with([
    ['duplicate slug', 'UNIQUE constraint failed: categories.slug'],
    ['duplicate review', 'UNIQUE constraint failed: reviews.booking_id'],
    ['invalid rating', 'CHECK constraint failed: rating between 1 and 5'],
    ['negative listing price', 'CHECK constraint failed: price_cents >= 0'],
    ['negative booking price', 'CHECK constraint failed: price_cents >= 0'],
    ['delete referenced category', 'FOREIGN KEY constraint failed'],
    ['delete referenced provider', 'FOREIGN KEY constraint failed'],
    ['delete referenced customer', 'FOREIGN KEY constraint failed'],
    ['delete booked service', 'FOREIGN KEY constraint failed'],
    ['delete reviewed booking', 'FOREIGN KEY constraint failed'],
]);
