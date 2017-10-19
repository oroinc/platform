<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DateTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\NumberToStringTransformer;

class NumberToStringTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform($value, $expected)
    {
        $transformer = new NumberToStringTransformer();
        $this->assertSame($expected, $transformer->transform($value));
    }

    public function transformDataProvider()
    {
        return [
            [null, ''],
            ['123', '123'],
            [123, '123'],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     */
    public function testTransformWithInvalidValue()
    {
        $transformer = new NumberToStringTransformer();
        $transformer->transform('a');
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($scale, $value, $expected)
    {
        $transformer = new NumberToStringTransformer($scale);
        $this->assertSame($expected, $transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider()
    {
        return [
            [null, '', null],
            [null, '1.23456789', '1.23456789'],
            [null, '-1.23456789', '-1.23456789'],
            [null, '.123456789', '0.123456789'],
            [null, '-.123456789', '-0.123456789'],
            [0, '9223372036854775807', '9223372036854775807'],
            [0, '-9223372036854775808', '-9223372036854775808'],
            [2, '1.23456789', '1.23'],
            [2, '-1.23456789', '-1.23'],
            [3, '1.23456789', '1.235'],
            [3, '-1.23456789', '-1.235'],
            [3, '1.234', '1.234'],
            [3, '-1.234', '-1.234'],
            [3, '.234', '0.234'],
            [3, '-.234', '-0.234'],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     */
    public function testReverseTransformWithNotStringValue()
    {
        $transformer = new NumberToStringTransformer();
        $transformer->reverseTransform(1);
    }

    /**
     * @dataProvider reverseTransformInvalidValueDataProvider
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     */
    public function testReverseTransformWithInvalidValue($scale, $value)
    {
        $transformer = new NumberToStringTransformer($scale);
        $transformer->reverseTransform($value);
    }

    public function reverseTransformInvalidValueDataProvider()
    {
        return [
            [null, 'test'],
            [0, 'test'],
            [3, 'test'],
            [0, '1.2'],
        ];
    }
}
