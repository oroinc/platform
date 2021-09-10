<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Model;

use Oro\Bundle\EntityExtendBundle\Model\EnumValue;

class EnumValueTest extends \PHPUnit\Framework\TestCase
{
    public function testIdGetterAndSetter()
    {
        $enumValue = new EnumValue();
        self::assertNull($enumValue->getId());
        $enumValue->setId('testId');
        self::assertEquals('testId', $enumValue->getId());
    }

    public function testLabelGetterAndSetter()
    {
        $enumValue = new EnumValue();
        self::assertNull($enumValue->getLabel());
        $enumValue->setLabel('test label');
        self::assertEquals('test label', $enumValue->getLabel());
    }

    public function testIsDefaultGetterAndSetter()
    {
        $enumValue = new EnumValue();
        self::assertNull($enumValue->getIsDefault());
        $enumValue->setIsDefault(true);
        self::assertEquals(true, $enumValue->getIsDefault());
    }

    public function testPriorityGetterAndSetter()
    {
        $enumValue = new EnumValue();
        self::assertNull($enumValue->getPriority());
        $enumValue->setPriority(100);
        self::assertEquals(100, $enumValue->getPriority());
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

        self::assertEquals($enumValue, EnumValue::createFromArray($array));
        self::assertEquals($array, $enumValue->toArray());
    }
}
