<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')->withErrors([
                'google' => 'Google sign-in expired or your browser session changed. Open the login page using the same address throughout sign-in, then try again.',
            ]);
        }

        $profile = $googleUser->getRaw();
        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        if (! $email || ! $googleId || ! ($profile['email_verified'] ?? false)) {
            return redirect()->route('login')->withErrors([
                'google' => 'Google sign-in requires a verified email address.',
            ]);
        }

        $user = User::where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::where('email', $email)->first();

            if ($user && $user->google_id !== null) {
                return redirect()->route('login')->withErrors([
                    'google' => 'This email address is already linked to another Google account.',
                ]);
            }

            if (! $user) {
                $user = User::create([
                    'name' => $googleUser->getName() ?: $email,
                    'email' => $email,
                    'password' => Hash::make(Str::random(40)),
                    'google_id' => $googleId,
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->google_id = $googleId;
                $user->save();
            }
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
