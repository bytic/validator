<?php

namespace ByTIC\Validator\Tests\Functions;

use ByTIC\Validator\Tests\AbstractTest;

/**
 * Class GeneralTest
 * @package ByTIC\Validator\Tests\Functions
 */
class GeneralTest extends AbstractTest
{
    /**
     * @dataProvider data_valid_email
     * @param string $email
     * @param bool $valid
     */
    public function test_valid_email($email, $valid)
    {
        static::assertSame(valid_email($email), $valid);
    }

    /**
     * @return array[]
     */
    public function data_valid_email()
    {
        return [
            ['test@yahoo.com', true],
            ['test@yahoo.co.uk', true],
            ['test@domain.notld', false],
        ];
    }
}
