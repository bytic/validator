<?php

declare(strict_types=1);

namespace ByTIC\Validator\Constraints\Cnp\Schema;

/**
 * Serial – represents the NNN (sequential serial number) component of a Romanian CNP.
 *
 * NNN is a three-digit number (001–999) that differentiates individuals of the same
 * gender born in the same place on the same date.
 *
 * @see https://github.com/vimishor/cnp-spec/blob/master/spec.md#nnn-component
 * @see https://github.com/alceanicu/cnp
 */
class Serial
{
    /** The three-digit serial number string, e.g. "148". */
    private string $value;

    /**
     * @param array<int> $digits All 13 CNP digits (uses positions 9, 10, 11)
     */
    public function __construct(array $digits)
    {
        $this->value = sprintf('%d%d%d', $digits[9], $digits[10], $digits[11]);
    }

    /** The three-digit serial number string. */
    public function getValue(): string
    {
        return $this->value;
    }
}
