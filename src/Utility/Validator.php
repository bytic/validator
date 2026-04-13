<?php

declare(strict_types=1);

namespace ByTIC\Validator\Utility;

use ByTIC\Validator\Constraints\Cif;
use ByTIC\Validator\Constraints\Cnp\Cnp;
use Symfony\Component\Validator\Validation;

/**
 * Class Validator.
 */
class Validator
{
    public static function cif($value): \Symfony\Component\Validator\ConstraintViolationListInterface
    {
        return self::validate($value, new Cif());
    }

    public static function cnp($value): \Symfony\Component\Validator\ConstraintViolationListInterface
    {
        return self::validate($value, new Cnp());
    }

    /**
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    protected static function validate($value, $constraints)
    {
        $validator = Validation::createValidator();

        return $validator->validate(
            $value,
            $constraints
        );
    }
}
