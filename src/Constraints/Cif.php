<?php

declare(strict_types=1);

namespace ByTIC\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class Cif.
 *
 * @see https://ro.wikipedia.org/wiki/Cod_de_identificare_fiscal%C4%83
 */
class Cif extends Constraint
{
    public $message = 'The cif "{{ string }}" is not valid.';
}
