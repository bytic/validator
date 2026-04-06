<?php

declare(strict_types=1);

namespace ByTIC\Validator\Tests\Functions;

use ByTIC\Validator\Tests\AbstractTest;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class GeneralTest.
 */
class GeneralTest extends AbstractTest
{
    /**
     * @dataProvider data_valid_email
     *
     * @param string $email
     * @param bool   $valid
     */
    #[DataProvider('data_valid_email')]
    public function testValidEmail($email, $valid)
    {
        static::assertSame(valid_email($email), $valid);
    }

    /**
     * @return array[]
     */
    public static function data_valid_email(): array
    {
        return [
            ['test@yahoo.com', true],
            ['test@yahoo.co.uk', true],
            ['test@domain.notld', false],
        ];
    }
}
