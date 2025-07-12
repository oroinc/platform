<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\EnumToStringTransformer;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Model\BackedEnumInt;
use PHPUnit\Framework\TestCase;

class EnumToStringTransformerTest extends TestCase
{
    private EnumToStringTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->transformer = new EnumToStringTransformer();
    }

    public function testTransform(): void
    {
        self::assertEquals('Item1', $this->transformer->transform(BackedEnumInt::Item1, [], []));
    }

    public function testTransformNullValue(): void
    {
        self::assertNull($this->transformer->transform(null, [], []));
    }

    public function testTransformInvalidValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected a PHP enum value, "string" given.');
        $this->transformer->transform('Invalid', [], []);
    }
}
