<?php

declare(strict_types=1);

namespace ByTIC\Validator\Constraints\Cnp;

use ByTIC\Validator\Constraints\Cnp\Schema\Cnp as CnpData;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * CnpValidator – validates a Romanian Personal Identification Number (C.N.P.).
 *
 * Accepts a string or integer value representing the 13-digit CNP.
 * On success the parsed {@see CnpData} schema object is stored on the constraint
 * and can be retrieved via {@see Cnp::getCnpData()}.
 *
 * @see https://github.com/vimishor/cnp-spec/blob/master/spec.md
 * @see https://github.com/alceanicu/cnp
 * @see https://github.com/vimishor/cnp-php/tree/develop/src
 * @see https://ro.wikipedia.org/wiki/Cod_numeric_personal
 */
class CnpValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Cnp) {
            throw new UnexpectedTypeException($constraint, Cnp::class);
        }

        // null is handled by NotNull / NotBlank constraints
        if (null === $value) {
            return;
        }

        $value = trim((string) $value);

        if ('' === $value) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();

            return;
        }

        $cnpData = new CnpData($value);

        if (!$cnpData->isValid()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();

            return;
        }

        $constraint->setCnpData($cnpData);
    }
}
