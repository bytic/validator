<?php

declare(strict_types=1);

namespace ByTIC\Validator\Constraints\Cnp\Schema;

use DateTime;

/**
 * County – represents the JJ (county code) component of a Romanian CNP.
 *
 * The two-digit JJ value encodes the Romanian county (or Bucharest district) in
 * which the person was born or domiciled when their documents were issued.
 *
 * Special cases:
 *   - Code 70 is a nationwide unique-registration code (always valid).
 *   - Codes 47 and 48 are valid for birth dates on or before 1979-12-19 and
 *     for birth years 2000-2099; in both windows they map to București.
 *
 * @see https://github.com/vimishor/cnp-spec/blob/master/spec.md#jj-component
 * @see https://github.com/vimishor/cnp-php/blob/develop/src/County.php
 * @see https://github.com/alceanicu/cnp
 */
class County
{
    /**
     * Map of valid two-digit county codes to county/district names.
     * Codes 47 and 48 are normalized to București and are subject to
     * additional date restrictions.
     */
    public const CODES = [
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
        '47' => 'București',
        '48' => 'București',
        '51' => 'Călărași',
        '52' => 'Giurgiu',
        '70' => 'Cod unic (înregistrare indiferent de județ)',
    ];

    /** The two-digit county code string, e.g. "40". */
    private string $code;

    /** Whether this county code is valid (optionally with respect to the birth date). */
    private bool $valid = false;

    /**
     * @param string        $code      Two-digit county code string (e.g. "40")
     * @param DateTime|null $birthDate Required to validate restricted codes 47 and 48
     */
    public function __construct(string $code, ?DateTime $birthDate = null)
    {
        $this->code = $code;
        $this->validate($birthDate);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function isValid(): bool
    {
        return $this->valid;
    }

    /** The two-digit county code string. */
    public function getCode(): string
    {
        return $this->code;
    }

    /** The human-readable county/district name, or null if the code is unknown. */
    public function getName(): ?string
    {
        return self::CODES[$this->code] ?? null;
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    private function validate(?DateTime $birthDate): void
    {
        // Code 70 is a special nationwide registration code – always valid
        if ($this->code === '70') {
            $this->valid = true;

            return;
        }

        // Codes 47/48 are valid for historical records up to 1979-12-19
        // and for modern records in 2000-2099.
        if (in_array($this->code, ['47', '48'], true)) {
            $cutoff = new DateTime('1979-12-19 00:00:00');
            $this->valid = false;

            if ($birthDate instanceof DateTime) {
                $year = (int) $birthDate->format('Y');
                $this->valid = $birthDate <= $cutoff || ($year >= 2000 && $year <= 2099);
            }

            return;
        }

        $this->valid = array_key_exists($this->code, self::CODES);
    }
}
