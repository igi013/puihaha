<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('members.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'string', 'max:255'], 'password' => ['required', 'string']]);
        if (app()->environment(['local', 'testing']) && Str::lower(trim($credentials['email'])) === 'admin') {
            $credentials['email'] = 'admin@membership.local';
        }
        $key = Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Please wait one minute.']);
        }
        if (! Auth::attempt($credentials + ['is_admin' => true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'These administrator credentials do not match our records.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('members.index'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
