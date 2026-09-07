<?php

namespace App\Support;

final class Money
{
    //convertit decimal a centavos int 
    public static function toCents(string|int|float $amount): int {
        return (int) round(((float) $amount) * 100);
    }

    //convertir centavos a decimal 
    public static function fromCents(int $cents): string {
        return number_format($cents / 100, 2, '.', '');
    }
}
