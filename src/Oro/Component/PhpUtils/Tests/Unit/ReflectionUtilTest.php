<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\ReflectionUtil;
use Oro\Component\PhpUtils\Tests\Unit\Stubs\TestObject1;
use Oro\Component\PhpUtils\Tests\Unit\Stubs\TestObject2;

class ReflectionUtilTest extends \PHPUnit\Framework\TestCase
{
    const TEST_NAMESPACE = 'Oro\Component\PhpUtils\Tests\Unit\Stubs\\';

    /**
     * @dataProvider getPropertyDataProvider
     */
    public function testGetProperty($object, $propertyName, $expectedDeclaringClass)
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

    public function getPropertyDataProvider()
    {
        return [
            [new TestObject1(), 'undefinedProperty', null],
            [new TestObject1(), 'publicProperty', self::TEST_NAMESPACE . 'TestObject1'],
            [new TestObject1(), 'protectedProperty', self::TEST_NAMESPACE . 'TestObject1'],
            [new TestObject1(), 'privateProperty', self::TEST_NAMESPACE . 'TestObject1'],
            [new TestObject1(), 'publicBaseProperty', self::TEST_NAMESPACE . 'AbstractTestObject'],
            [new TestObject1(), 'protectedBaseProperty', self::TEST_NAMESPACE . 'AbstractTestObject'],
            [new TestObject1(), 'privateBaseProperty', self::TEST_NAMESPACE . 'AbstractTestObject'],
            [new TestObject2(), 'publicBaseProperty', self::TEST_NAMESPACE . 'TestObject2'],
            [new TestObject2(), 'protectedBaseProperty', self::TEST_NAMESPACE . 'TestObject2'],
            [new TestObject2(), 'privateBaseProperty', self::TEST_NAMESPACE . 'TestObject2'],
        ];
    }
}
