<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Model;

use Oro\Bundle\EntityExtendBundle\Model\EnumOption;
use PHPUnit\Framework\TestCase;

class EnumValueTest extends TestCase
{
    public function testIdGetterAndSetter(): void
    {
        $enumValue = new EnumOption();
        self::assertNull($enumValue->getId());
        $enumValue->setId('testId');
        self::assertEquals('testId', $enumValue->getId());
    }

    public function testLabelGetterAndSetter(): void
    {
        $enumValue = new EnumOption();
        self::assertNull($enumValue->getLabel());
        $enumValue->setLabel('test label');
        self::assertEquals('test label', $enumValue->getLabel());
    }

    public function testIsDefaultGetterAndSetter(): void
    {
        $enumValue = new EnumOption();
        self::assertNull($enumValue->getIsDefault());
        $enumValue->setIsDefault(true);
        self::assertEquals(true, $enumValue->getIsDefault());
    }

    public function testPriorityGetterAndSetter(): void
    {
        $enumValue = new EnumOption();
        self::assertNull($enumValue->getPriority());
        $enumValue->setPriority(100);
        self::assertEquals(100, $enumValue->getPriority());
    }

    public function testFromArrayAndToArray(): void
    {
        $array = [
            'id' => 'testId',
            'label' => 'test label',
            'is_default' => true,
            'priority' => 100,
        ];

        $enumValue = new EnumOption();
        $enumValue
            ->setLabel('test label')
            ->setId('testId')
            ->setIsDefault(true)
            ->setPriority(100);

        self::assertEquals($enumValue, EnumOption::createFromArray($array));
        self::assertEquals($array, $enumValue->toArray());
    }
}
