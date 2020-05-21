<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\EmptyArrayToNullTransformer;

class EmptyArrayToNullTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmptyArrayToNullTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new EmptyArrayToNullTransformer();
    }

    public function testTransformNull()
    {
        $value = null;
        self::assertNull(
            $this->transformer->transform($value, [], [])
        );
    }

    public function testTransformEmptyArray()
    {
        $value = [];
        self::assertNull(
            $this->transformer->transform($value, [], [])
        );
    }

    public function testTransformArray()
    {
        $value = ['key' => 'value'];
        self::assertSame(
            $value,
            $this->transformer->transform($value, [], [])
        );
    }
}
