<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints\Decimal;

class DecimalTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param $options
     *
     * @dataProvider constructorProvider
     */
    public function testConstructor($options, $expetedPrecision, $expectedScale)
    {
        $constraint = new Decimal($options);

        $this->assertEquals($constraint->precision, $expetedPrecision);
        $this->assertEquals($constraint->scale, $expectedScale);
    }

    /**
     * @return array
     */
    public function constructorProvider()
    {
        return [
            [['precision' => 6,    'scale' => 2   ], 6,  2],
            [['precision' => null, 'scale' => 2   ], 10, 2],
            [['precision' => 6,    'scale' => null], 6,  0],
            [['precision' => null, 'scale' => null], 10, 0],
        ];
    }
}
