<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\Entity\Enum;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class AbstractEnumValueTest extends \PHPUnit_Framework_TestCase
{
    /** @var Enum */
    protected $enum;

    protected function setUp()
    {
        $this->enum = new Enum();
    }

    public function testConstructor()
    {
        $enumValue = new TestEnumValue('test', 'Test', $this->enum);

        $this->assertEquals('test', $enumValue->getCode());
        $this->assertEquals('Test', $enumValue->getName());
        $this->assertSame($this->enum, $enumValue->getEnum());
        $this->assertEquals(0, $enumValue->getPriority());
        $this->assertFalse($enumValue->isDefault());
    }

    public function testConstructorWithAllParams()
    {
        $enumValue = new TestEnumValue('test', 'Test', $this->enum, 123, true);

        $this->assertEquals('test', $enumValue->getCode());
        $this->assertEquals('Test', $enumValue->getName());
        $this->assertSame($this->enum, $enumValue->getEnum());
        $this->assertEquals(123, $enumValue->getPriority());
        $this->assertTrue($enumValue->isDefault());
    }

    public function testNameGetterAndSetter()
    {
        $enumValue = new TestEnumValue('test', 'Test', $this->enum);

        $this->assertEquals($enumValue, $enumValue->setName('Test1'));
        $this->assertEquals('Test1', $enumValue->getName());
    }

    public function testEnumGetterAndSetter()
    {
        $enumValue = new TestEnumValue('test', 'Test', $this->enum);

        $enum1 = new Enum();
        $this->assertEquals($enumValue, $enumValue->setEnum($enum1));
        $this->assertSame($enum1, $enumValue->getEnum());
    }

    public function testPriorityGetterAndSetter()
    {
        $enumValue = new TestEnumValue('test', 'Test', $this->enum);

        $this->assertEquals($enumValue, $enumValue->setPriority(123));
        $this->assertEquals(123, $enumValue->getPriority());
    }

    public function testDefaultGetterAndSetter()
    {
        $enumValue = new TestEnumValue('test', 'Test', $this->enum);

        $this->assertEquals($enumValue, $enumValue->setDefault(true));
        $this->assertTrue($enumValue->isDefault());
    }

    public function testLocaleGetterAndSetter()
    {
        $enumValue = new TestEnumValue('test', 'Test', $this->enum);

        $this->assertEquals($enumValue, $enumValue->setLocale('fr'));
        $this->assertEquals('fr', $enumValue->getLocale());
    }
}
