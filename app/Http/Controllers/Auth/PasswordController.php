<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    /**
     * Show the change-password form for the logged-in user.
     */
    public function edit(): View
    {
        return view('auth.change-password');
    }

    /**
     * Update the logged-in user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], [
            'current_password' => 'current password',
            'password'         => 'new password',
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], (string) $user->password)) {
            return back()
                ->withErrors(['current_password' => 'The current password is incorrect.'])
                ->onlyInput('current_password');
        }

        if (Hash::check($data['password'], (string) $user->password)) {
            return back()->withErrors(['password' => 'The new password must be different from the current password.']);
        }

        $user->forceFill([
            'password'              => $data['password'], // 'hashed' cast handles hashing
            'password_last_changed' => now()->toDateTimeString(),
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('password.edit')->with('status', 'Password changed successfully.');
    }
}
