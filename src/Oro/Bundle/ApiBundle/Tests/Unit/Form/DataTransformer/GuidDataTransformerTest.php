<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\GuidDataTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class GuidDataTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $value = 'eac12975-d94d-4e96-88b1-101b99914def';
        $transformer = new GuidDataTransformer();
        self::assertSame($value, $transformer->transform($value));
    }

    public function testTransformWithNullValue(): void
    {
        $transformer = new GuidDataTransformer();
        self::assertSame('', $transformer->transform(null));
    }

    public function testTransformWithEmptyStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new GuidDataTransformer();
        $transformer->transform('');
    }

    public function testTransformWithNotStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new GuidDataTransformer();
        $transformer->transform(1);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(string $value, ?string $expected): void
    {
        $transformer = new GuidDataTransformer();
        self::assertSame($expected, $transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            ['', null],
            ['eac12975-d94d-4e96-88b1-101b99914def', 'eac12975-d94d-4e96-88b1-101b99914def'],
            ['EAC12975-D94D-4E96-88B1-101B99914DEF', 'EAC12975-D94D-4E96-88B1-101B99914DEF']
        ];
    }

    public function testReverseTransformWithNotStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new GuidDataTransformer();
        $transformer->reverseTransform(1);
    }

    /**
     * @dataProvider reverseTransformInvalidValueDataProvider
     */
    public function testReverseTransformWithInvalidValue(string $value): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new GuidDataTransformer();
        $transformer->reverseTransform($value);
    }

    public function reverseTransformInvalidValueDataProvider(): array
    {
        return [
            ['test'],
            ['7eab7435-44bb-493a-9bda-dea3fda3c0dh'],
            ['7eab7435-44bb-493a-9bda-dea3fda3c0d91']
        ];
    }
}
