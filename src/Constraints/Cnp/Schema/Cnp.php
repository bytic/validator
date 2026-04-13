<?php

declare(strict_types=1);

namespace ByTIC\Validator\Constraints\Cnp\Schema;

use DateTime;

/**
 * Cnp – root schema object for a parsed Romanian Personal Identification Number (C.N.P.).
 *
 * CNP structure: [S][AA][LL][ZZ][JJ][NNN][C]
 *   S   – {@see Gender}   – gender / century digit (1–8)
 *   AA  – {@see Birthdate} – last two digits of birth year
 *   LL  – {@see Birthdate} – birth month (01–12)
 *   ZZ  – {@see Birthdate} – birth day   (01–31)
 *   JJ  – {@see County}   – county code (01–52, 70)
 *   NNN – {@see Serial}   – sequential serial number (001–999)
 *   C   – {@see Checksum} – control / checksum digit
 *
 * Each component is instantiated as a property during construction.  All public
 * accessor methods remain fully backward-compatible.
 *
 * @see https://github.com/vimishor/cnp-spec/blob/master/spec.md
 * @see https://github.com/alceanicu/cnp
 * @see https://github.com/vimishor/cnp-php/tree/develop/src
 * @see https://ro.wikipedia.org/wiki/Cod_numeric_personal
 */
class Cnp
{
    /**
     * Backward-compatible alias for {@see Checksum::CONTROL_KEY}.
     */
    public const CONTROL_KEY = Checksum::CONTROL_KEY;

    /**
     * Backward-compatible alias for {@see County::CODES}.
     */
    public const COUNTY_CODE = County::CODES;

    // -------------------------------------------------------------------------
    // Raw data
    // -------------------------------------------------------------------------

    /** The raw input string. */
    private string $raw = '';

    /** Individual digits of the CNP (index 0–12). */
    private array $digits = [];

    /** Overall validity flag – true only if all components pass. */
    private bool $valid = false;

    // -------------------------------------------------------------------------
    // Component properties
    // -------------------------------------------------------------------------

    private ?Gender $gender = null;

    private ?Birthdate $birthdate = null;

    private ?County $county = null;

    private ?Serial $serial = null;

    private ?Checksum $checksum = null;

    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    /**
     * @param string $cnp Raw 13-digit CNP string
     */
    public function __construct(string $cnp)
    {
        $this->raw = $cnp;
        $this->parse();
    }

    // -------------------------------------------------------------------------
    // Component accessors
    // -------------------------------------------------------------------------

    public function getGenderComponent(): ?Gender
    {
        return $this->gender;
    }

    public function getBirthdateComponent(): ?Birthdate
    {
        return $this->birthdate;
    }

    public function getCountyComponent(): ?County
    {
        return $this->county;
    }

    public function getSerialComponent(): ?Serial
    {
        return $this->serial;
    }

    public function getChecksumComponent(): ?Checksum
    {
        return $this->checksum;
    }

    // -------------------------------------------------------------------------
    // Public API (backward-compatible accessors)
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

    /** The S digit (1–8). */
    public function getGenderCode(): ?int
    {
        return $this->gender?->getCode();
    }

    /** "M" for male, "F" for female, or null if the CNP did not parse a valid gender. */
    public function getGender(): ?string
    {
        return $this->gender?->getGenderString();
    }

    public function isResident(): bool
    {
        return $this->gender?->isResident() ?? false;
    }

    public function getBirthYear(): ?int
    {
        return $this->birthdate?->getYear();
    }

    public function getBirthMonth(): ?string
    {
        return $this->birthdate?->getMonth();
    }

    public function getBirthDay(): ?string
    {
        return $this->birthdate?->getDay();
    }

    public function getBirthDate(): ?DateTime
    {
        return $this->birthdate?->getDate();
    }

    public function getBirthDateFormatted(string $format = 'Y-m-d'): ?string
    {
        return $this->birthdate?->getFormatted($format);
    }

    public function getCountyCode(): ?string
    {
        return $this->county?->getCode();
    }

    public function getCounty(): ?string
    {
        return $this->county?->getName();
    }

    public function getSerialNumber(): ?string
    {
        return $this->serial?->getValue();
    }

    public function isMajor(): bool
    {
        return $this->valid && ($this->birthdate?->getAgeInYears() ?? 0) >= 18;
    }

    public function hasIdentityCard(): bool
    {
        return $this->valid && ($this->birthdate?->getAgeInYears() ?? 0) >= 14;
    }

    // -------------------------------------------------------------------------
    // Parsing pipeline
    // -------------------------------------------------------------------------

    private function parse(): void
    {
        if (!$this->parseSyntax()) {
            return;
        }

        // 1. Gender / century digit (S)
        $this->gender = new Gender($this->digits[0]);
        if (!$this->gender->isValid()) {
            return;
        }

        // 2. Birth date (AA/LL/ZZ)
        $this->birthdate = new Birthdate($this->digits, $this->gender->getCode());
        if (!$this->birthdate->isValid()) {
            return;
        }

        // 3. County code (JJ) – pass the birth date for defunct-district validation
        $this->county = new County(
            sprintf('%02d', ($this->digits[7] * 10) + $this->digits[8]),
            $this->birthdate->getDate()
        );
        if (!$this->county->isValid()) {
            return;
        }

        // 4. Serial number (NNN) – always structurally valid given three digits
        $this->serial = new Serial($this->digits);

        // 5. Checksum (C)
        $this->checksum = new Checksum($this->digits);
        if (!$this->checksum->isValid()) {
            return;
        }

        $this->valid = true;
    }

    /**
     * Validates basic format (exactly 13 ASCII digit characters) and populates $this->digits.
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
}
