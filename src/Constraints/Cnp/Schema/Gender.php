<?php

declare(strict_types=1);

namespace ByTIC\Validator\Constraints\Cnp\Schema;

/**
 * Gender – represents the S (gender/century) digit of a Romanian CNP.
 *
 * The S digit encodes both the person's gender and the century of birth:
 *   1 = Male,   born 1900–1999
 *   2 = Female, born 1900–1999
 *   3 = Male,   born 1800–1899
 *   4 = Female, born 1800–1899
 *   5 = Male,   born 2000–2099
 *   6 = Female, born 2000–2099
 *   7 = Male resident  (century not encoded)
 *   8 = Female resident (century not encoded)
 *
 * Odd values → male; even values → female.
 *
 * @see https://github.com/vimishor/cnp-spec/blob/master/spec.md#s-component
 * @see https://github.com/vimishor/cnp-php/blob/develop/src/Gender.php
 * @see https://github.com/alceanicu/cnp
 */
class Gender
{
    /** Valid S digit range. */
    private const VALID_CODES = [1, 2, 3, 4, 5, 6, 7, 8];

    /** S digit value. */
    private int $code;

    public function __construct(int $code)
    {
        $this->code = $code;
    }

    /** Whether the S digit falls within the valid range (1–8). */
    public function isValid(): bool
    {
        return in_array($this->code, self::VALID_CODES, true);
    }

    /** The raw S digit (1–8). */
    public function getCode(): int
    {
        return $this->code;
    }

    /** Whether the person is male (odd S digit). */
    public function isMale(): bool
    {
        return $this->code % 2 !== 0;
    }

    /** Whether the person is female (even S digit). */
    public function isFemale(): bool
    {
        return $this->code % 2 === 0;
    }

    /**
     * Whether the person is a resident (S digit is 7 or 8).
     * Residents' CNPs do not encode the century directly.
     */
    public function isResident(): bool
    {
        return in_array($this->code, [7, 8], true);
    }

    /**
     * Returns "M" for male or "F" for female, or null if the S digit is invalid.
     */
    public function getGenderString(): ?string
    {
        if (!$this->isValid()) {
            return null;
        }

        return $this->isMale() ? 'M' : 'F';
    }
}
