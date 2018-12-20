<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class AbstractEnumValueTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor()
    {
        $enumValue = new TestEnumValue('test', 'Test');

        $this->assertEquals('test', $enumValue->getId());
        $this->assertEquals('Test', $enumValue->getName());
        $this->assertEquals(0, $enumValue->getPriority());
        $this->assertFalse($enumValue->isDefault());
    }

    public function testConstructorWithAllParams()
    {
        $enumValue = new TestEnumValue('test', 'Test', 123, true);

        $this->assertEquals('test', $enumValue->getId());
        $this->assertEquals('Test', $enumValue->getName());
        $this->assertEquals(123, $enumValue->getPriority());
        $this->assertTrue($enumValue->isDefault());
    }

    public function testNameGetterAndSetter()
    {
        $enumValue = new TestEnumValue('test', 'Test');

        $this->assertEquals($enumValue, $enumValue->setName('Test1'));
        $this->assertEquals('Test1', $enumValue->getName());
    }

    public function testPriorityGetterAndSetter()
    {
        $enumValue = new TestEnumValue('test', 'Test');

        $this->assertEquals($enumValue, $enumValue->setPriority(123));
        $this->assertEquals(123, $enumValue->getPriority());
    }

    public function testDefaultGetterAndSetter()
    {
        $enumValue = new TestEnumValue('test', 'Test');

        $this->assertEquals($enumValue, $enumValue->setDefault(true));
        $this->assertTrue($enumValue->isDefault());
    }

    public function testLocaleGetterAndSetter()
    {
        $enumValue = new TestEnumValue('test', 'Test');

        $this->assertEquals($enumValue, $enumValue->setLocale('fr'));
        $this->assertEquals('fr', $enumValue->getLocale());
    }
}
