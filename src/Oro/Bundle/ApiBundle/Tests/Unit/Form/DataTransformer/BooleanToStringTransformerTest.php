<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\BooleanToStringTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class BooleanToStringTransformerTest extends TestCase
{
    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(?bool $value, string $expected): void
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

    public function testTransformWithInvalidValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new BooleanToStringTransformer();
        $transformer->transform(1);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(string $value, ?bool $expected): void
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

    public function testReverseTransformWithNotStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new BooleanToStringTransformer();
        $transformer->reverseTransform(1);
    }

    public function testReverseTransformWithInvalidValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new BooleanToStringTransformer();
        $transformer->reverseTransform('test');
    }
}
