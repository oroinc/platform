<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\NumberToStringTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;

class NumberToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(string|int|null $value, string $expected)
    {
        $transformer = new NumberToStringTransformer();
        self::assertSame($expected, $transformer->transform($value));
    }

    public function transformDataProvider(): array
    {
        return [
            [null, ''],
            ['123', '123'],
            [123, '123']
        ];
    }

    public function testTransformWithInvalidValue()
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new NumberToStringTransformer();
        $transformer->transform('a');
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(?int $scale, string $value, ?string $expected)
    {
        $transformer = new NumberToStringTransformer($scale);
        self::assertSame($expected, $transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
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
            [3, '-.234', '-0.234']
        ];
    }

    public function testReverseTransformWithNotStringValue()
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new NumberToStringTransformer();
        $transformer->reverseTransform(1);
    }

    /**
     * @dataProvider reverseTransformInvalidValueDataProvider
     */
    public function testReverseTransformWithInvalidValue(?int $scale, string $value)
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new NumberToStringTransformer($scale);
        $transformer->reverseTransform($value);
    }

    public function reverseTransformInvalidValueDataProvider(): array
    {
        return [
            [null, 'test'],
            [0, 'test'],
            [3, 'test'],
            [0, '1.2']
        ];
    }
}
