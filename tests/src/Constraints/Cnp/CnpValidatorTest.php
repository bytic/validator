<?php

declare(strict_types=1);

namespace ByTIC\Validator\Tests\Constraints\Cnp;

use ByTIC\Validator\Constraints\Cnp\Cnp;
use ByTIC\Validator\Constraints\Cnp\Schema\Cnp as CnpData;
use ByTIC\Validator\Tests\AbstractTest;
use ByTIC\Validator\Utility\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * CnpValidatorTest – tests for the CNP Symfony constraint and validator.
 *
 * Test CNP values sourced from:
 * @see https://github.com/alceanicu/cnp
 * @see https://github.com/vimishor/cnp-spec/blob/master/spec.md
 */
class CnpValidatorTest extends AbstractTest
{
    // -------------------------------------------------------------------------
    // Constraint / Validator wiring
    // -------------------------------------------------------------------------

    public function testConstraintIsSymfonyConstraint(): void
    {
        $constraint = new Cnp();
        self::assertInstanceOf(\Symfony\Component\Validator\Constraint::class, $constraint);
    }

    public function testConstraintDefaultMessage(): void
    {
        $constraint = new Cnp();
        self::assertStringContainsString('CNP', $constraint->message);
    }

    // -------------------------------------------------------------------------
    // Utility helper
    // -------------------------------------------------------------------------

    /**
     * @dataProvider cnpDataProvider
     */
    #[DataProvider('cnpDataProvider')]
    public function testCnpViaUtility(mixed $cnp, bool $isValid): void
    {
        $violations = Validator::cnp($cnp);
        $result = 0 === count($violations);
        self::assertSame($isValid, $result, "CNP [{$cnp}] validation mismatch");
    }

    // -------------------------------------------------------------------------
    // CnpData schema object
    // -------------------------------------------------------------------------

    public function testValidCnpPopulatesCnpData(): void
    {
        // CNP: 5110102441483
        // S=5 (male, 2000-2099), AA=11, LL=01, ZZ=02, JJ=44 (Sector 4), NNN=148, C=3
        $cnpData = new CnpData('5110102441483');

        self::assertTrue($cnpData->isValid());
        self::assertSame('M', $cnpData->getGender());
        self::assertSame(2011, $cnpData->getBirthYear());
        self::assertSame('01', $cnpData->getBirthMonth());
        self::assertSame('02', $cnpData->getBirthDay());
        self::assertSame('44', $cnpData->getCountyCode());
        self::assertSame('București Sector 4', $cnpData->getCounty());
        self::assertSame('148', $cnpData->getSerialNumber());
        self::assertFalse($cnpData->isResident());
    }

    public function testInvalidCnpDoesNotPopulateCnpData(): void
    {
        $cnpData = new CnpData('0000000000000');
        self::assertFalse($cnpData->isValid());
        self::assertNull($cnpData->getGender());
        self::assertNull($cnpData->getBirthDate());
        self::assertNull($cnpData->getCounty());
    }

    public function testConstraintStoresCnpDataAfterValidation(): void
    {
        $constraint = new Cnp();
        $validator = \Symfony\Component\Validator\Validation::createValidator();
        $violations = $validator->validate('5110102441483', $constraint);

        self::assertCount(0, $violations);
        self::assertInstanceOf(CnpData::class, $constraint->getCnpData());
        self::assertTrue($constraint->getCnpData()->isValid());
    }

    public function testCnpDataBirthDateFormat(): void
    {
        $cnpData = new CnpData('5110102441483');
        self::assertSame('2011-01-02', $cnpData->getBirthDateFormatted('Y-m-d'));
    }

    public function testMaleCnp(): void
    {
        // 1800101400016: S=1 (male, 1900-1999), born 1980-01-01, Bucharest
        $cnpData = new CnpData('1800101400016');
        self::assertTrue($cnpData->isValid());
        self::assertSame('M', $cnpData->getGender());
        self::assertSame(1980, $cnpData->getBirthYear());
    }

    public function testFemaleCnp(): void
    {
        // 2900615400027: S=2 (female, 1900-1999), born 1990-06-15, Bucharest
        $cnpData = new CnpData('2900615400027');
        self::assertTrue($cnpData->isValid());
        self::assertSame('F', $cnpData->getGender());
        self::assertSame(1990, $cnpData->getBirthYear());
    }

    public function testResidentCnp(): void
    {
        // 7850410400054: S=7 (male resident), born ~1985-04-10, Bucharest
        $cnpData = new CnpData('7850410400054');
        self::assertTrue($cnpData->isValid());
        self::assertSame(7, $cnpData->getGenderCode());
        self::assertSame('M', $cnpData->getGender());
        self::assertTrue($cnpData->isResident());
    }

    public function testEighteenthCenturyCnp(): void
    {
        // 4500228400016: S=4 (female, 1800-1899), born 1850-02-28, Bucharest
        $cnpData = new CnpData('4500228400016');
        self::assertTrue($cnpData->isValid());
        self::assertSame('F', $cnpData->getGender());
        self::assertSame(1850, $cnpData->getBirthYear());
    }

    public function testNullValueIsIgnored(): void
    {
        $violations = Validator::cnp(null);
        self::assertCount(0, $violations);
    }

    // -------------------------------------------------------------------------
    // Data providers
    // -------------------------------------------------------------------------

    /**
     * @return array<array{mixed, bool}>
     */
    public static function cnpDataProvider(): array
    {
        return [
            // ---- edge / invalid ----
            [null,              true],   // null is silently skipped (use NotNull separately)
            ['',               false],
            ['   ',            false],
            ['abc',            false],
            ['123',            false],
            ['000000000000',   false],   // 12 digits
            ['00000000000000', false],   // 14 digits
            ['0000000000000',  false],   // 13 zeros – invalid S digit
            ['1234567890123',  false],   // wrong checksum

            // ---- valid CNPs ----
            // female, born 2011-01-02, Sector 4 Bucharest
            ['5110102441483',  true],
            // male, born 1980-01-01, Bucharest
            ['1800101400016',  true],
            // female, born 1990-06-15, Bucharest
            ['2900615400027',  true],
            // male, born 2001-03-20, Cluj
            ['5010320120033',  true],
            // female, born 1975-11-25, Iași
            ['2751125220045',  true],
            // female, born 1850-02-28, Bucharest (1800s)
            ['4500228400016',  true],

            // ---- checksum failures ----
            ['5110102441484',  false],   // last digit changed by 1
        ];
    }
}
