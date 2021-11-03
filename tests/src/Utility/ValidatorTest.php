<?php

namespace ByTIC\Validator\Tests\Utility;

use ByTIC\Validator\Tests\AbstractTest;
use ByTIC\Validator\Utility\Validator;

/**
 * Class ValidatorTest
 * @package ByTIC\Validator\Tests\Utility
 */
class ValidatorTest extends AbstractTest
{
    /**
     * @param $cif
     * @param $isValid
     * @dataProvider cifDataProvider
     */
    public function test_cif($cif, $isValid)
    {
        $violations = Validator::cif($cif);
        $validationResult = count($violations) == 0;
        self::assertSame($isValid, $validationResult, "CIF [$cif] does not validate");
    }

    /**
     * @return array
     */
    public function cifDataProvider()
    {
        return [
            #CIF, isValid
            [null, true],
            [false, false],
            [true, false],
            [0, false],
            ['0', false],
            ['', true],
            ['   ', false],
            [-1, false],
            [1, false],
            [999999999999, false],
            ['10', false],
            ['xxx', false],
            ['-1a', false],
            ['-1', false],
            // some real CIF
            [9010105, true],    # ORANGE ROMANIA SA - http://www.mfinante.gov.ro/infocodfiscal.html?cod=9010105
            ['RO 9010105', true],
            [5888716, true],    # RCS & RDS SA - http://www.mfinante.gov.ro/infocodfiscal.html?cod=5888716
            ['R5888716', false],
            [8971726, true],    # VODAFONE ROMANIA SA - http://www.mfinante.gov.ro/infocodfiscal.html?cod=8971726
            ['89717 26', false],
            [159, true],        # FRIGOTEHNICA SRL - http://www.mfinante.gov.ro/infocodfiscal.html?cod=159
            ['RO159', true],
            [19, true],         # BUCUR OBOR S.A - http://www.mfinante.gov.ro/infocodfiscal.html?cod=19
            [' 19 ', true],
            ['32586219', true],
        ];
    }
}

