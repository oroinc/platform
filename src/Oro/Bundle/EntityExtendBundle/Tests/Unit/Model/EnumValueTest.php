<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Model;

use Oro\Bundle\EntityExtendBundle\Model\EnumValue;

class EnumValueTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGetterAndSetter()
    {
        $enumValue = new EnumValue();
        static::assertNull($enumValue->getId());
        $enumValue->setId('testId');
        static::assertEquals('testId', $enumValue->getId());
    }

    public function testLabelGetterAndSetter()
    {
        $enumValue = new EnumValue();
        static::assertNull($enumValue->getLabel());
        $enumValue->setLabel('test label');
        static::assertEquals('test label', $enumValue->getLabel());
    }

    public function testIsDefaultGetterAndSetter()
    {
        $enumValue = new EnumValue();
        static::assertNull($enumValue->getIsDefault());
        $enumValue->setIsDefault(true);
        static::assertEquals(true, $enumValue->getIsDefault());
    }

    public function testPriorityGetterAndSetter()
    {
        $enumValue = new EnumValue();
        static::assertNull($enumValue->getPriority());
        $enumValue->setPriority(100);
        static::assertEquals(100, $enumValue->getPriority());
    }

    public function testFromArrayAndToArray()
    {
        $array = [
            'id' => 'testId',
            'label' => 'test label',
            'is_default' => true,
            'priority' => 100,
        ];

        $enumValue = new EnumValue();
        $enumValue
            ->setLabel('test label')
            ->setId('testId')
            ->setIsDefault(true)
            ->setPriority(100);

        static::assertEquals($enumValue, EnumValue::createFromArray($array));
        static::assertEquals($array, $enumValue->toArray());
    }
}
