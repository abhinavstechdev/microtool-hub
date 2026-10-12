<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = (bool) $request->input('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            AuditLog::record(Auth::id(), null, 'auth:login');

            // If there was a pending OAuth request, resume it
            if ($pendingOAuth = session('oauth_pending_request')) {
                session()->forget('oauth_pending_request');
                return redirect()->route('oauth.authorize', $pendingOAuth);
            }

            return redirect()->intended(route('dashboard.index'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => Str::slug($validated['name']) . '-' . Str::random(4),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
        ]);

        Auth::login($user);
        AuditLog::record($user->id, null, 'auth:register');

        if ($pendingOAuth = session('oauth_pending_request')) {
            session()->forget('oauth_pending_request');
            return redirect()->route('oauth.authorize', $pendingOAuth);
        }

        return redirect()->route('dashboard.index');
    }

    public function logout(Request $request)
    {
        AuditLog::record(Auth::id(), null, 'auth:logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
