<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserCardNumberTest extends TestCase
{
    public function test_card_numbers_are_16_digits_luhn_valid_and_unique_per_user(): void
    {
        $seen = [];

        foreach (range(1, 5000) as $id) {
            $user = new User;
            $user->id = $id;
            $number = $user->cardNumber();

            $this->assertMatchesRegularExpression('/^\d{16}$/', $number);
            $this->assertTrue($this->luhn($number), "Invalid checksum for id $id");
            $this->assertArrayNotHasKey($number, $seen, "Duplicate number for id $id");
            $seen[$number] = true;
        }
    }

    public function test_card_number_is_stable_for_a_user(): void
    {
        $user = new User;
        $user->id = 42;

        $this->assertSame($user->cardNumber(), $user->cardNumber());
    }

    private function luhn(string $number): bool
    {
        $sum = 0;
        foreach (array_reverse(str_split($number)) as $i => $digit) {
            $d = (int) $digit;
            if ($i % 2 === 1) {
                $d *= 2;
                $d -= $d > 9 ? 9 : 0;
            }
            $sum += $d;
        }

        return $sum % 10 === 0;
    }
}
