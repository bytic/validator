<?php

declare(strict_types=1);

namespace ByTIC\Validator\Constraints\Cnp\Schema;

use DateTime;

/**
 * Birthdate – parses and validates the AA/LL/ZZ (birth year, month, day) components of a CNP.
 *
 * The full birth year is derived from the two-digit AA value and the S (gender/century) digit:
 *   S ∈ {1,2} → 1900 + AA
 *   S ∈ {3,4} → 1800 + AA
 *   S ∈ {5,6} → 2000 + AA
 *   S ∈ {7,8} → 2000 + AA, then subtract 100 if the resulting year is in the future
 *               relative to (currentYear − 14)
 *
 * @see https://github.com/vimishor/cnp-spec/blob/master/spec.md#aa-component
 * @see https://github.com/alceanicu/cnp
 */
class Birthdate
{
    /** Parsed four-digit birth year. */
    private ?int $year = null;

    /** Two-digit birth month string, e.g. "03". */
    private ?string $month = null;

    /** Two-digit birth day string, e.g. "07". */
    private ?string $day = null;

    /** Parsed birth DateTime (midnight). */
    private ?DateTime $date = null;

    /** Whether the birth date is valid. */
    private bool $valid = false;

    /**
     * @param array<int> $digits All 13 CNP digits (uses positions 1–6)
     * @param int        $genderCode The S digit (1–8), used to determine the century
     */
    public function __construct(array $digits, int $genderCode)
    {
        $this->parse($digits, $genderCode);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function getMonth(): ?string
    {
        return $this->month;
    }

    public function getDay(): ?string
    {
        return $this->day;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function getFormatted(string $format = 'Y-m-d'): ?string
    {
        return $this->date instanceof DateTime ? $this->date->format($format) : null;
    }

    /** Returns the person's age in complete years as of today, or 0 if the date is unavailable. */
    public function getAgeInYears(): int
    {
        if (!$this->date instanceof DateTime) {
            return 0;
        }

        return (int) $this->date
            ->diff((new DateTime())->setTime(0, 0))
            ->format('%y');
    }

    // -------------------------------------------------------------------------
    // Parsing
    // -------------------------------------------------------------------------

    private function parse(array $digits, int $genderCode): void
    {
        $this->year = $this->computeYear($digits, $genderCode);
        $this->month = sprintf('%02d', ($digits[3] * 10) + $digits[4]);
        $this->day = sprintf('%02d', ($digits[5] * 10) + $digits[6]);

        if ($this->year === null || $this->year < 1800 || $this->year > 2099) {
            return;
        }

        $month = (int) $this->month;
        $day = (int) $this->day;

        if ($month < 1 || $month > 12) {
            return;
        }

        if ($day < 1 || $day > 31) {
            return;
        }

        if ($day > 28 && !checkdate($month, $day, $this->year)) {
            return;
        }

        $this->date = new DateTime(
            sprintf('%04d-%02d-%02d 00:00:00', $this->year, $month, $day)
        );
        $this->valid = true;
    }

    private function computeYear(array $digits, int $genderCode): ?int
    {
        $yearShort = ($digits[1] * 10) + $digits[2];

        if (in_array($genderCode, [1, 2], true)) {
            return 1900 + $yearShort;
        }

        if (in_array($genderCode, [3, 4], true)) {
            return 1800 + $yearShort;
        }

        if (in_array($genderCode, [5, 6], true)) {
            return 2000 + $yearShort;
        }

        if (in_array($genderCode, [7, 8], true)) {
            $year = 2000 + $yearShort;
            if ($year > ((int) date('Y') - 14)) {
                $year -= 100;
            }

            return $year;
        }

        return null;
    }
}
