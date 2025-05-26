<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\EmptyArrayToNullTransformer;
use PHPUnit\Framework\TestCase;

class EmptyArrayToNullTransformerTest extends TestCase
{
    private EmptyArrayToNullTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->transformer = new EmptyArrayToNullTransformer();
    }

    public function testTransformNull(): void
    {
        $value = null;
        self::assertNull(
            $this->transformer->transform($value, [], [])
        );
    }

    public function testTransformEmptyArray(): void
    {
        $value = [];
        self::assertNull(
            $this->transformer->transform($value, [], [])
        );
    }

    public function testTransformArray(): void
    {
        $value = ['key' => 'value'];
        self::assertSame(
            $value,
            $this->transformer->transform($value, [], [])
        );
    }
}
