<?php

namespace App\Support;

/**
 * Форматирование денежных сумм: взаиморасчёты ведутся в рублях.
 */
class Money
{
    public static function format(float|int|string|null $value): string
    {
        $amount = (float) $value;

        $formatted = fmod($amount, 1.0) === 0.0
            ? number_format($amount, 0, ',', ' ')
            : number_format($amount, 2, ',', ' ');

        return $formatted.' ₽';
    }
}
