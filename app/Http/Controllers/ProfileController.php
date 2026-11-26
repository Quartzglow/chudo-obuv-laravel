<?php

namespace App\Http\Controllers;

use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly Cart $cart) {}

    public function show(): View
    {
        $user = auth()->user();

        return view('profile.show', [
            'user' => $user,
            'ordersCount' => $user->orders()->count(),
            'lastOrders' => $user->orders()
                ->with('items')
                ->orderByDesc('ordered_at')
                ->orderByDesc('id')
                ->limit(3)
                ->get(),
            'cartRows' => $this->cart->rows(),
            'cartTotal' => $this->cart->total(),
        ]);
    }

    public function edit(): View
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    /**
     * Редактирование данных пользователя.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'surname' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:100'],
            'patronymic' => ['nullable', 'string', 'max:100'],
            'login' => ['required', 'string', 'max:100', Rule::unique('users', 'login')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
        ], [], [
            'surname' => 'фамилия',
            'name' => 'имя',
            'patronymic' => 'отчество',
            'login' => 'логин',
            'email' => 'адрес электронной почты',
            'phone' => 'номер телефона',
        ]);

        $user->update($data);

        return redirect()->route('profile.show')->with('status', 'Данные профиля сохранены.');
    }

    public function passwordForm(): View
    {
        return view('profile.password');
    }

    /**
     * Изменение пароля авторизованным пользователем.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [], [
            'current_password' => 'текущий пароль',
            'password' => 'новый пароль',
        ]);

        if (! Hash::check($data['current_password'], (string) $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Текущий пароль указан неверно.']);
        }

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return redirect()->route('profile.show')->with('status', 'Пароль изменён.');
    }
}
