<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the application login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle a login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login_email' => ['required', 'string', 'email'],
            'password'    => ['required', 'string'],
        ]);

        $remember = (bool) $request->boolean('remember');

        // Only let active, unlocked users in
        $extra = ['active_flag' => true, 'locked_flag' => false];

        if (Auth::attempt($credentials + $extra, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();
            $user->forceFill([
                'last_login_at'     => now(),
                'last_login_ip'     => $request->ip(),
                'last_login_device' => substr((string) $request->userAgent(), 0, 255),
                'login_attempts'    => 0,
            ])->save();

            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'login_email' => __('These credentials do not match our records.'),
        ]);
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
