<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\ReflectionUtil;
use Oro\Component\PhpUtils\Tests\Unit\Stubs\AbstractTestObject;
use Oro\Component\PhpUtils\Tests\Unit\Stubs\TestObject1;
use Oro\Component\PhpUtils\Tests\Unit\Stubs\TestObject2;

class ReflectionUtilTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getPropertyDataProvider
     */
    public function testGetProperty(object $object, string $propertyName, ?string $expectedDeclaringClass)
    {
        $property = ReflectionUtil::getProperty(new \ReflectionClass($object), $propertyName);
        if (null === $expectedDeclaringClass) {
            $this->assertNull($property);
        } else {
            $this->assertEquals(
                $expectedDeclaringClass,
                $property->getDeclaringClass()->getName()
            );
        }
    }

    public function getPropertyDataProvider(): array
    {
        return [
            [new TestObject1(), 'undefinedProperty', null],
            [new TestObject1(), 'publicProperty', TestObject1::class],
            [new TestObject1(), 'protectedProperty', TestObject1::class],
            [new TestObject1(), 'privateProperty', TestObject1::class],
            [new TestObject1(), 'publicBaseProperty', AbstractTestObject::class],
            [new TestObject1(), 'protectedBaseProperty', AbstractTestObject::class],
            [new TestObject1(), 'privateBaseProperty', AbstractTestObject::class],
            [new TestObject2(), 'publicBaseProperty', TestObject2::class],
            [new TestObject2(), 'protectedBaseProperty', TestObject2::class],
            [new TestObject2(), 'privateBaseProperty', TestObject2::class],
        ];
    }
}
