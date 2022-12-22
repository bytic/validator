<?php

declare(strict_types=1);

namespace ByTIC\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Class CifValidator.
 */
class CifValidator extends ConstraintValidator
{
    private static $controlKey = [7, 5, 3, 2, 1, 7, 5, 3, 2];

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Cif) {
            throw new UnexpectedTypeException($constraint, Cif::class);
        }

        if (null === $value) {
            return;
        }

        $value = (string) $value;
        $value = trim($value);
        if (empty($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (!is_numeric($value) && !\is_string($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'string');
            // separate multiple types using pipes
            // throw new UnexpectedValueException($value, 'string|int');
        }

        if (false === $this->validateCIF($value)) {
            // the argument must be a string or an object implementing __toString()
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }

    protected function validateCIF($cif)
    {
        // Daca este string, elimina atributul fiscal si spatiile
        if (!is_numeric($cif)) {
            $cif = strtoupper($cif);
            if (0 === strpos($cif, 'RO')) {
                $cif = substr($cif, 2);
            }
            $cif = (int) trim($cif);
        }

        if ((int) $cif <= 0) {
            return false;
        }

        $cif = (string) $cif;
        $cifLength = \strlen($cif);
        // daca are mai mult de 10 cifre sau mai putin de 2, nu-i valid
        if ($cifLength > 10 || $cifLength < 2) {
            return false;
        }

        // extrage cifra de control
        $controlKey = (int) substr($cif, -1);

        $cif = substr($cif, 0, -1);
        $cif = str_pad($cif, 9, '0', \STR_PAD_LEFT);
        $suma = 0;
        foreach (self::$controlKey as $i => $key) {
            $suma += (int) $cif[$i] * (int) $key;
        }
        $suma = $suma * 10;
        $rest = (int) ($suma % 11);

        // daca modulo 11 este 10, atunci cifra de control este 0
        $rest = (10 == $rest) ? 0 : $rest;

        return $rest === $controlKey;
    }
}
