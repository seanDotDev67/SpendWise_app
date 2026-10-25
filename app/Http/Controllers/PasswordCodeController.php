<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordCodeController extends Controller
{
    private const MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    // Step 1: email -> send the 6-digit code
    public function send(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        $email = Str::lower($request->email);

        $this->issueCode($email);
        session(['reset_email' => $email]);
        session()->forget('reset_verified');

        return redirect()->route('password.code.verify.show');
    }

    // Step 2: show the code page
    public function showVerify()
    {
        $email = session('reset_email');
        if (! $email) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-email', ['maskedEmail' => $this->mask($email)]);
    }

    public function verify(Request $request)
    {
        $email = session('reset_email');
        if (! $email) {
            return redirect()->route('password.request');
        }

        $request->validate(['code' => ['required', 'digits:6']], [
            'code.required' => 'Please enter the 6-digit code.',
            'code.digits'   => 'Please enter the 6-digit code.',
        ]);

        $attemptsKey = "pwd_reset_attempts:{$email}";
        if (Cache::get($attemptsKey, 0) >= self::MAX_ATTEMPTS) {
            return back()->withErrors(['code' => 'Too many tries. Please resend a new code.']);
        }

        $hash = Cache::get("pwd_reset_code:{$email}");
        if (! $hash || ! Hash::check($request->code, $hash)) {
            Cache::put($attemptsKey, Cache::get($attemptsKey, 0) + 1, now()->addMinutes(self::MINUTES));
            return back()->withErrors(['code' => 'That code is wrong or has expired.']);
        }

        Cache::forget("pwd_reset_code:{$email}");
        Cache::forget($attemptsKey);
        session(['reset_verified' => $email]);

        return redirect()->route('password.code.reset.show');
    }

    public function resend()
    {
        $email = session('reset_email');
        if (! $email) {
            return redirect()->route('password.request');
        }

        $this->issueCode($email);

        return back()->with('status', 'A new code has been sent to your email.');
    }

    // Step 3: new password
    public function showReset()
    {
        if (! session('reset_verified')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password');
    }

    public function reset(Request $request)
    {
        $email = session('reset_verified');
        if (! $email) {
            return redirect()->route('password.request');
        }

        $request->validate(['password' => ['required', 'confirmed', Password::min(8)]]);

        $user = User::where('email', $email)->first();
        if ($user) {
            $user->forceFill(['password' => Hash::make($request->password)])->save();
        }

        session()->forget(['reset_email', 'reset_verified']);

        return redirect()->route('login')->with('status', 'Password updated. You can sign in now.');
    }

    private function issueCode(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            return; // same response either way, so nobody can check which emails are registered
        }

        $code = (string) random_int(100000, 999999);
        Cache::put("pwd_reset_code:{$email}", Hash::make($code), now()->addMinutes(self::MINUTES));
        Cache::forget("pwd_reset_attempts:{$email}");

        Mail::raw(
            "Your SpendWise verification code is {$code}.\n\nIt expires in " . self::MINUTES . " minutes. If you didn't ask for this, you can ignore this email.",
            function ($m) use ($email) {
                $m->to($email)->subject('Your SpendWise reset code');
            }
        );
    }

    private function mask(string $email): string
    {
        [$name, $domain] = explode('@', $email);

        return Str::substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 1)) . '@' . $domain;
    }
}