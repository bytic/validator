<?php

declare(strict_types=1);

namespace ByTIC\Validator\Constraints\Cnp\Schema;

/**
 * Checksum – calculates and validates the C (control/checksum) digit of a Romanian CNP.
 *
 * The checksum is computed using the constant 279146358279 as follows:
 *   1. Each of the first 12 CNP digits is multiplied by the corresponding
 *      digit of the control key [2,7,9,1,4,6,3,5,8,2,7,9].
 *   2. The products are summed and the total is divided by 11.
 *   3. If the remainder is < 10, it is the checksum digit.
 *   4. If the remainder is 10, the checksum digit is 1.
 *
 * @see https://github.com/vimishor/cnp-spec/blob/master/spec.md#validation
 * @see https://github.com/alceanicu/cnp
 */
class Checksum
{
    /**
     * The 12-position control key used in the checksum calculation.
     * Represents the constant 279146358279 split into individual digits.
     */
    public const CONTROL_KEY = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];

    /** The calculated expected checksum digit. */
    private int $expected;

    /** The actual checksum digit found in the CNP. */
    private int $actual;

    /**
     * @param array<int> $digits All 13 CNP digits
     */
    public function __construct(array $digits)
    {
        $this->actual = $digits[12];
        $this->expected = $this->compute($digits);
    }

    /** Whether the CNP checksum digit matches the calculated value. */
    public function isValid(): bool
    {
        return $this->actual === $this->expected;
    }

    /** The expected checksum digit as computed from the first 12 digits. */
    public function getExpected(): int
    {
        return $this->expected;
    }

    /** The actual checksum digit as found in position 12 of the CNP. */
    public function getActual(): int
    {
        return $this->actual;
    }

    private function compute(array $digits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $digits[$i] * self::CONTROL_KEY[$i];
        }

        $remainder = $sum % 11;

        return ($remainder === 10) ? 1 : $remainder;
    }
}
