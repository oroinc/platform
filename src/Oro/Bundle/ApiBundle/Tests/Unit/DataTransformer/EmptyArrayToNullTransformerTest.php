<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\EmptyArrayToNullTransformer;

class EmptyArrayToNullTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmptyArrayToNullTransformer */
    private $transformer;

    protected function setUp()
    {
        $this->transformer = new EmptyArrayToNullTransformer();
    }

    public function testTransformNull()
    {
        $value = null;
        self::assertNull(
            $this->transformer->transform('Test\Class', 'testProp', $value, [], [])
        );
    }

    public function testTransformEmptyArray()
    {
        $value = [];
        self::assertNull(
            $this->transformer->transform('Test\Class', 'testProp', $value, [], [])
        );
    }

    public function testTransformArray()
    {
        $value = ['key' => 'value'];
        self::assertSame(
            $value,
            $this->transformer->transform('Test\Class', 'testProp', $value, [], [])
        );
    }
}
