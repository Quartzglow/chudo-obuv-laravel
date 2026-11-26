<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\UserResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Восстановление пароля. Отправка письма не настроена, поэтому ссылка на сброс
 * выводится на экране (демонстрационный режим учебного приложения).
 */
class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ], [], [
            'identifier' => 'логин, телефон или e-mail',
        ]);

        $user = UserResolver::byIdentifier($data['identifier']);

        if (! $user) {
            return back()
                ->withInput()
                ->withErrors(['identifier' => 'Пользователь с такими данными не найден в списке пользователей системы.']);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $token, 'created_at' => now()],
        );

        return redirect()
            ->route('password.request')
            ->with('status', 'Ссылка для восстановления пароля сформирована.')
            ->with('reset_link', route('password.reset', ['token' => $token, 'email' => $user->email]));
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [], [
            'password' => 'новый пароль',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (! $record || ! hash_equals($record->token, $data['token'])) {
            return back()->withInput()->withErrors(['email' => 'Ссылка для восстановления пароля недействительна.']);
        }

        $createdAt = $record->created_at ? Carbon::parse($record->created_at) : null;

        if (! $createdAt || $createdAt->diffInMinutes(now()) > 60) {
            return back()->withInput()->withErrors(['email' => 'Срок действия ссылки истёк. Запросите восстановление повторно.']);
        }

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return back()->withInput()->withErrors(['email' => 'Пользователь не найден.']);
        }

        $user->update(['password' => Hash::make($data['password'])]);
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return redirect()->route('login')->with('status', 'Пароль изменён. Войдите с новым паролем.');
    }
}
