<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_floats_lose_precision_when_summing_decimals(): void
    {
        // En binario 0.1 + 0.2 != 0.3; el (string) lo esconde redondeando
        // con la precisión por defecto (14 dígitos). %.17f lo deja al desnudo.
        $this->assertNotSame('0.3', sprintf('%.17f', 0.1 + 0.2));
    }

    public function test_accumulating_in_cents_is_exact(): void
    {
        $totalCents = Money::toCents('0.10') + Money::toCents('0.20');

        $this->assertSame(30, $totalCents);
        $this->assertSame('0.30', Money::fromCents($totalCents));
    }

    public function test_prices_like_2999_convert_to_exact_cents(): void
    {
        $this->assertSame(2999, Money::toCents('29.99'));
    }

    public function test_multi_item_cart_total_is_exact(): void
    {
        $items = [
            ['quantity' => 3, 'unit_price' => '29.99'],
            ['quantity' => 2, 'unit_price' => '9.99'],
            ['quantity' => 1, 'unit_price' => '19.99'],
        ];

        $totalCents = 0;
        foreach ($items as $item) {
            $totalCents += $item['quantity'] * Money::toCents($item['unit_price']);
        }

        $this->assertSame(12994, $totalCents);
        $this->assertSame('129.94', Money::fromCents($totalCents));
    }
}
