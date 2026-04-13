<?php

declare(strict_types=1);

namespace ByTIC\Validator\Constraints\Cnp;

use ByTIC\Validator\Constraints\Cnp\Schema\Cnp as CnpData;
use Symfony\Component\Validator\Constraint;

/**
 * Cnp – Symfony constraint for Romanian Personal Identification Number (C.N.P.).
 *
 * Usage:
 *   #[Cnp]
 *   private string $cnp;
 *
 * After validation the parsed data object is available via getCnpData().
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @see https://github.com/vimishor/cnp-spec/blob/master/spec.md
 * @see https://github.com/alceanicu/cnp
 * @see https://github.com/vimishor/cnp-php/tree/develop/src
 * @see https://ro.wikipedia.org/wiki/Cod_numeric_personal
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Cnp extends Constraint
{
    public string $message = 'The CNP "{{ string }}" is not valid.';

    /** Populated by CnpValidator after a successful validation pass. */
    private ?CnpData $cnpData = null;

    public function setCnpData(CnpData $cnpData): void
    {
        $this->cnpData = $cnpData;
    }

    public function getCnpData(): ?CnpData
    {
        return $this->cnpData;
    }
}
