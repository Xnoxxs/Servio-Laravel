<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Shared\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SignInController extends Controller
{
    public function showSignIn(): View
    {
        return view('auth.register');
    }

    public function signIn(RegisterRequest $request): RedirectResponse
    {
        try {
            $user = User::create($request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => __('validation.unique', ['attribute' => 'email'])]);
        }

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('home');
    }
}
