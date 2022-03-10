<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\BooleanToStringTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;

class BooleanToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(?bool $value, string $expected)
    {
        $transformer = new BooleanToStringTransformer();
        self::assertEquals($expected, $transformer->transform($value));
    }

    public function transformDataProvider(): array
    {
        return [
            [null, ''],
            [true, 'true'],
            [false, 'false']
        ];
    }

    public function testTransformWithInvalidValue()
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new BooleanToStringTransformer();
        $transformer->transform(1);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(string $value, ?bool $expected)
    {
        $transformer = new BooleanToStringTransformer();
        self::assertEquals($expected, $transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            ['', null],
            ['true', true],
            ['yes', true],
            ['1', true],
            ['false', false],
            ['no', false],
            ['0', false]
        ];
    }

    public function testReverseTransformWithNotStringValue()
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new BooleanToStringTransformer();
        $transformer->reverseTransform(1);
    }

    public function testReverseTransformWithInvalidValue()
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new BooleanToStringTransformer();
        $transformer->reverseTransform('test');
    }
}
