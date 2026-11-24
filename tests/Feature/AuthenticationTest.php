<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('visitors can open the public home and account forms', function () {
    $this->withoutVite();

    $this->get(route('home'))->assertOk()->assertSee(route('login'));
    $this->get(route('login'))->assertOk()->assertSee(route('login.store'));
    $this->get(route('register'))->assertOk()->assertSee(route('register.store'));
});

test('registration signs in a member without accepting admin privileges', function () {
    $this->post(route('register.store'), [
        'name' => 'Servio Member',
        'email' => 'member@example.com',
        'password' => 'example-password',
        'password_confirmation' => 'example-password',
        'is_admin' => true,
    ])->assertRedirect(route('home'));

    $user = User::where('email', 'member@example.com')->sole();

    $this->assertAuthenticatedAs($user);
    expect($user->is_admin)->toBeFalse()
        ->and(Hash::check('example-password', $user->password))->toBeTrue();
});

test('registration rejects an email address already in use', function () {
    $existingUser = User::factory()->create();

    $this->from(route('register'))->post(route('register.store'), [
        'name' => 'Another Member',
        'email' => $existingUser->email,
        'password' => 'example-password',
        'password_confirmation' => 'example-password',
    ])->assertRedirect(route('register'))->assertSessionHasErrors('email');

    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});

test('existing users can log in and terminate their session', function () {
    $this->withoutVite();

    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);

    $this->get(route('register'))->assertRedirect(route('home'));
    $this->get(route('home'))->assertOk()->assertSee(route('logout'));
    $this->post(route('logout'))->assertRedirect(route('home'));
    $this->assertGuest();
});

test('visitors are redirected to login when posting logout', function () {
    $this->post(route('logout'))->assertRedirect(route('login'));
});

test('repeated failed attempts temporarily block login from the same client', function () {
    $user = User::factory()->create();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.failed')]);
    }

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('invalid account form input renders validation messages after redirect', function () {
    $this->withoutVite();

    $this->followingRedirects()->from(route('register'))->post(route('register.store'), [
        'name' => ['invalid'],
        'email' => ['invalid'],
    ])->assertOk()
        ->assertSee('The name field must be a string.')
        ->assertSee('The email field must be a string.');

    $this->assertGuest();
});
