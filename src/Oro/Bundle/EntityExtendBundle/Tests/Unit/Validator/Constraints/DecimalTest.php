<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Validator\Constraints\Decimal;

class DecimalTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider constructorProvider
     */
    public function testConstructor(array $options, int $expectedPrecision, int $expectedScale)
    {
        $constraint = new Decimal($options);

        $this->assertSame($constraint->precision, $expectedPrecision);
        $this->assertSame($constraint->scale, $expectedScale);
    }

    public function constructorProvider(): array
    {
        return [
            [['precision' => 6, 'scale' => 2], 6, 2],
            [['precision' => null, 'scale' => 2], 10, 2],
            [['precision' => 6, 'scale' => null], 6, 0],
            [['precision' => null, 'scale' => null], 10, 0],
        ];
    }
}
