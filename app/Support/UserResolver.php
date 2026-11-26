<?php

namespace App\Support;

use App\Models\User;

/**
 * Поиск пользователя по логину, номеру телефона или адресу электронной почты.
 */
class UserResolver
{
    public static function byIdentifier(string $identifier): ?User
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        $user = User::where('login', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if ($user) {
            return $user;
        }

        $digits = self::digits($identifier);

        if (strlen($digits) >= 10) {
            return User::all()->first(
                fn (User $candidate) => self::digits((string) $candidate->phone) === $digits
            );
        }

        return null;
    }

    public static function digits(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        // Номер может быть указан как 8XXXXXXXXXX, +7XXXXXXXXXX или XXXXXXXXXX.
        if (strlen($digits) === 10) {
            $digits = '7'.$digits;
        }

        if (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7'.substr($digits, 1);
        }

        return $digits;
    }
}
