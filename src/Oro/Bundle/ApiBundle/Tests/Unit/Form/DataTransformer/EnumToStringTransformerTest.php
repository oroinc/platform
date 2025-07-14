<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\EnumToStringTransformer;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Model\BackedEnumInt;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EnumToStringTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = new EnumToStringTransformer(BackedEnumInt::class);
        self::assertSame('Item1', $transformer->transform(BackedEnumInt::Item1));
    }

    public function testTransformWithNullValue(): void
    {
        $transformer = new EnumToStringTransformer(BackedEnumInt::class);
        self::assertSame('', $transformer->transform(null));
    }

    public function testTransformWithEmptyStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new EnumToStringTransformer(BackedEnumInt::class);
        $transformer->transform('');
    }

    public function testTransformWithNotStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new EnumToStringTransformer(BackedEnumInt::class);
        $transformer->transform(1);
    }

    public function testReverseTransform(): void
    {
        $transformer = new EnumToStringTransformer(BackedEnumInt::class);
        self::assertSame(BackedEnumInt::Item1, $transformer->reverseTransform('Item1'));
    }

    public function testReverseTransformWithEmptyStringValue(): void
    {
        $transformer = new EnumToStringTransformer(BackedEnumInt::class);
        self::assertNull($transformer->reverseTransform(''));
    }

    public function testReverseTransformWithNotStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new EnumToStringTransformer(BackedEnumInt::class);
        $transformer->reverseTransform(1);
    }

    public function testReverseTransformWithInvalidValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new EnumToStringTransformer(BackedEnumInt::class);
        $transformer->reverseTransform('UndefinedItem');
    }
}
