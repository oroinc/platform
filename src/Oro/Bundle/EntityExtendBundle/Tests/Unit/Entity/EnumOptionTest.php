<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class EnumOptionTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor()
    {
        $testName = 'test';
        $enumValue = new TestEnumValue('test_enum_code', $testName, $testName, 1);

        $this->assertEquals('test_enum_code.test', $enumValue->getId());
        $this->assertEquals($testName, $enumValue->getName());
        $this->assertEquals('test_enum_code', $enumValue->getEnumCode());
        $this->assertEquals($testName, $enumValue->getInternalId());
        $this->assertEquals(1, $enumValue->getPriority());
        $this->assertFalse($enumValue->isDefault());
    }

    public function testConstructorWithAllParams()
    {
        $testName = 'test';
        $enumValue = new TestEnumValue(
            'test_enum_code',
            $testName,
            $testName,
            1,
            true
        );

        $this->assertEquals('test_enum_code.test', $enumValue->getId());
        $this->assertEquals($testName, $enumValue->getName());
        $this->assertEquals('test_enum_code', $enumValue->getEnumCode());
        $this->assertEquals($testName, $enumValue->getInternalId());
        $this->assertEquals(1, $enumValue->getPriority());
        $this->assertTrue($enumValue->isDefault());
    }

    public function testNameGetterAndSetter()
    {
        $testName = 'test';
        $enumValue = new TestEnumValue('test_enum_code', $testName, $testName, 1);

        $this->assertEquals($enumValue, $enumValue->setName('Test1'));
        $this->assertEquals('Test1', $enumValue->getName());
    }

    public function testPriorityGetterAndSetter()
    {
        $testName = 'test';
        $enumValue = new TestEnumValue('test_enum_code', $testName, $testName, 1);

        $this->assertEquals($enumValue, $enumValue->setPriority(123));
        $this->assertEquals(123, $enumValue->getPriority());
    }

    public function testDefaultGetterAndSetter()
    {
        $testName = 'test';
        $enumValue = new TestEnumValue('test_enum_code', $testName, $testName, 1);

        $this->assertEquals($enumValue, $enumValue->setDefault(true));
        $this->assertTrue($enumValue->isDefault());
    }

    public function testLocaleGetterAndSetter()
    {
        $testName = 'test';
        $enumValue = new TestEnumValue('test_enum_code', $testName, $testName, 1);

        $this->assertEquals($enumValue, $enumValue->setLocale('fr'));
        $this->assertEquals('fr', $enumValue->getLocale());
    }
}
