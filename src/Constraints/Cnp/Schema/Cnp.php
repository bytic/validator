<?php

declare(strict_types=1);

namespace ByTIC\Validator\Constraints\Cnp\Schema;

use DateTime;

/**
 * Cnp - Data Transfer Object for a parsed Romanian Personal Identification Number (CNP).
 *
 * CNP structure: [S][AA][LL][ZZ][JJ][NNN][C]
 *   S   – gender / century digit (1–8)
 *   AA  – last two digits of birth year
 *   LL  – birth month (01–12)
 *   ZZ  – birth day   (01–31)
 *   JJ  – county code (01–52, 70)
 *   NNN – sequential serial number (001–999)
 *   C   – control / checksum digit
 *
 * @see https://github.com/vimishor/cnp-spec/blob/master/spec.md
 * @see https://github.com/alceanicu/cnp
 * @see https://github.com/vimishor/cnp-php/tree/develop/src
 * @see https://ro.wikipedia.org/wiki/Cod_numeric_personal
 */
class Cnp
{
    /**
     * Control key used to calculate the checksum digit.
     * Each position corresponds to one of the first 12 CNP digits.
     */
    public const CONTROL_KEY = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];

    /**
     * Map of county codes to county names.
     * Codes 47 and 48 (Bucharest Districts 7 and 8) are now defunct.
     */
    public const COUNTY_CODE = [
        '01' => 'Alba',
        '02' => 'Arad',
        '03' => 'Argeș',
        '04' => 'Bacău',
        '05' => 'Bihor',
        '06' => 'Bistrița-Năsăud',
        '07' => 'Botoșani',
        '08' => 'Brașov',
        '09' => 'Brăila',
        '10' => 'Buzău',
        '11' => 'Caraș-Severin',
        '12' => 'Cluj',
        '13' => 'Constanța',
        '14' => 'Covasna',
        '15' => 'Dâmbovița',
        '16' => 'Dolj',
        '17' => 'Galați',
        '18' => 'Gorj',
        '19' => 'Harghita',
        '20' => 'Hunedoara',
        '21' => 'Ialomița',
        '22' => 'Iași',
        '23' => 'Ilfov',
        '24' => 'Maramureș',
        '25' => 'Mehedinți',
        '26' => 'Mureș',
        '27' => 'Neamț',
        '28' => 'Olt',
        '29' => 'Prahova',
        '30' => 'Satu Mare',
        '31' => 'Sălaj',
        '32' => 'Sibiu',
        '33' => 'Suceava',
        '34' => 'Teleorman',
        '35' => 'Timiș',
        '36' => 'Tulcea',
        '37' => 'Vaslui',
        '38' => 'Vâlcea',
        '39' => 'Vrancea',
        '40' => 'București',
        '41' => 'București Sector 1',
        '42' => 'București Sector 2',
        '43' => 'București Sector 3',
        '44' => 'București Sector 4',
        '45' => 'București Sector 5',
        '46' => 'București Sector 6',
        '47' => 'București Sector 7 (now defunct)',
        '48' => 'București Sector 8 (now defunct)',
        '51' => 'Călărași',
        '52' => 'Giurgiu',
        '70' => 'Cod unic (înregistrare indiferent de județ)',
    ];

    /** Whether the CNP is valid. */
    private bool $valid = false;

    /** The raw 13-digit CNP string. */
    private string $raw = '';

    /** Individual digits of the CNP (index 0–12). */
    private array $digits = [];

    /** The S digit (1–8): encodes gender and century. */
    private ?int $genderCode = null;

    /** Full four-digit birth year. */
    private ?int $birthYear = null;

    /** Two-digit birth month string, e.g. "03". */
    private ?string $birthMonth = null;

    /** Two-digit birth day string, e.g. "07". */
    private ?string $birthDay = null;

    /** Parsed birth date. */
    private ?DateTime $birthDate = null;

    /** "M" (male) or "F" (female). */
    private ?string $gender = null;

    /** Two-digit county code string, e.g. "40". */
    private ?string $countyCode = null;

    /** Full county name. */
    private ?string $county = null;

    /** Three-digit sequential serial number string, e.g. "148". */
    private ?string $serialNumber = null;

    /** Whether the person is a resident (S digit is 7 or 8). */
    private bool $resident = false;

    /**
     * @param string $cnp Raw 13-digit CNP string
     */
    public function __construct(string $cnp)
    {
        $this->raw = $cnp;
        $this->parse();
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getRaw(): string
    {
        return $this->raw;
    }

    public function getDigits(): array
    {
        return $this->digits;
    }

    public function getGenderCode(): ?int
    {
        return $this->genderCode;
    }

    public function getBirthYear(): ?int
    {
        return $this->birthYear;
    }

    public function getBirthMonth(): ?string
    {
        return $this->birthMonth;
    }

    public function getBirthDay(): ?string
    {
        return $this->birthDay;
    }

    public function getBirthDate(): ?DateTime
    {
        return $this->birthDate;
    }

    public function getBirthDateFormatted(string $format = 'Y-m-d'): ?string
    {
        return $this->birthDate instanceof DateTime
            ? $this->birthDate->format($format)
            : null;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function getCountyCode(): ?string
    {
        return $this->countyCode;
    }

    public function getCounty(): ?string
    {
        return $this->county;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function isResident(): bool
    {
        return $this->resident;
    }

    public function isMajor(): bool
    {
        return $this->valid && $this->getAgeInYears() >= 18;
    }

    public function hasIdentityCard(): bool
    {
        return $this->valid && $this->getAgeInYears() >= 14;
    }

    // -------------------------------------------------------------------------
    // Parsing
    // -------------------------------------------------------------------------

    private function parse(): void
    {
        if (!$this->parseSyntax()) {
            return;
        }

        $this->parseGenderCode();
        $this->parseBirthParts();
        $this->parseCounty();
        $this->parseSerial();

        if (!$this->validateDate()) {
            return;
        }

        if (!$this->validateCounty()) {
            return;
        }

        if (!$this->validateChecksum()) {
            return;
        }

        $this->valid = true;
    }

    /**
     * Validate basic format: exactly 13 ASCII digits.
     */
    private function parseSyntax(): bool
    {
        if (strlen($this->raw) !== 13 || !ctype_digit($this->raw)) {
            return false;
        }

        foreach (str_split($this->raw) as $ch) {
            $this->digits[] = (int) $ch;
        }

        return true;
    }

    private function parseGenderCode(): void
    {
        $s = $this->digits[0];
        $this->genderCode = $s;
        $this->resident = in_array($s, [7, 8], true);

        if (in_array($s, [1, 3, 5, 7], true)) {
            $this->gender = 'M';
        } elseif (in_array($s, [2, 4, 6, 8], true)) {
            $this->gender = 'F';
        }
    }

    private function parseBirthParts(): void
    {
        $yearShort = ($this->digits[1] * 10) + $this->digits[2];

        if (in_array($this->genderCode, [1, 2], true)) {
            $this->birthYear = 1900 + $yearShort;
        } elseif (in_array($this->genderCode, [3, 4], true)) {
            $this->birthYear = 1800 + $yearShort;
        } elseif (in_array($this->genderCode, [5, 6], true)) {
            $this->birthYear = 2000 + $yearShort;
        } elseif (in_array($this->genderCode, [7, 8], true)) {
            // residents: disambiguate century based on current year
            $this->birthYear = 2000 + $yearShort;
            if ($this->birthYear > ((int) date('Y') - 14)) {
                $this->birthYear -= 100;
            }
        }

        $this->birthMonth = sprintf('%02d', ($this->digits[3] * 10) + $this->digits[4]);
        $this->birthDay = sprintf('%02d', ($this->digits[5] * 10) + $this->digits[6]);
    }

    private function parseCounty(): void
    {
        $this->countyCode = sprintf('%02d', ($this->digits[7] * 10) + $this->digits[8]);
        $this->county = self::COUNTY_CODE[$this->countyCode] ?? null;
    }

    private function parseSerial(): void
    {
        $this->serialNumber = sprintf(
            '%d%d%d',
            $this->digits[9],
            $this->digits[10],
            $this->digits[11]
        );
    }

    private function validateDate(): bool
    {
        if ($this->birthYear === null || $this->birthYear < 1800 || $this->birthYear > 2099) {
            return false;
        }

        $month = (int) $this->birthMonth;
        $day = (int) $this->birthDay;

        if ($month < 1 || $month > 12) {
            return false;
        }

        if ($day < 1 || $day > 31) {
            return false;
        }

        if ($day > 28 && !checkdate($month, $day, $this->birthYear)) {
            return false;
        }

        $this->birthDate = new DateTime(
            sprintf('%04d-%02d-%02d 00:00:00', $this->birthYear, $month, $day)
        );

        return true;
    }

    private function validateCounty(): bool
    {
        // Code 70 is a special nationwide code
        if ($this->countyCode === '70') {
            return true;
        }

        // Defunct Bucharest Districts 7 and 8 are only valid for dates on or before 1979-12-19
        if (in_array($this->countyCode, ['47', '48'], true)) {
            $cutoff = new DateTime('1979-12-19 00:00:00');
            return $this->birthDate instanceof DateTime && $this->birthDate <= $cutoff;
        }

        return array_key_exists($this->countyCode, self::COUNTY_CODE);
    }

    private function validateChecksum(): bool
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $this->digits[$i] * self::CONTROL_KEY[$i];
        }

        $remainder = $sum % 11;
        $expected = ($remainder === 10) ? 1 : $remainder;

        return $this->digits[12] === $expected;
    }

    private function getAgeInYears(): int
    {
        if (!$this->birthDate instanceof DateTime) {
            return 0;
        }

        return (int) $this->birthDate
            ->diff((new DateTime())->setTime(0, 0))
            ->format('%y');
    }
}
