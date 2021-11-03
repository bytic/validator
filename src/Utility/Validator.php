<?php

namespace ByTIC\Validator\Utility;

use ByTIC\Validator\Constraints\Cif;
use Symfony\Component\Validator\Validation;

/**
 * Class Validator
 * @package ByTIC\Validator\Utility
 */
class Validator
{
    public static function cif($value): \Symfony\Component\Validator\ConstraintViolationListInterface
    {
        return self::validate($value, new Cif());
    }

    /**
     * @param $value
     * @param $constraints
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
