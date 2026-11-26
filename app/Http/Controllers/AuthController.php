<?php

namespace App\Http\Controllers;

use App\Support\UserResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Вход в систему по логину, номеру телефона или адресу электронной почты.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [], [
            'identifier' => 'логин, телефон или e-mail',
            'password' => 'пароль',
        ]);

        $user = UserResolver::byIdentifier($credentials['identifier']);

        if (! $user || ! Hash::check($credentials['password'], (string) $user->password)) {
            return back()
                ->withInput($request->only('identifier'))
                ->withErrors(['identifier' => 'Неверный логин, телефон, e-mail или пароль.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('home'))
            ->with('status', 'Добро пожаловать, '.$user->full_name.'!');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Вы вышли из системы.');
    }
}
