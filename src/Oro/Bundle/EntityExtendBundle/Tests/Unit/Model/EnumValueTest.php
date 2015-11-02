<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Model;

use Oro\Bundle\EntityExtendBundle\Model\EnumValue;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class EnumValueTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var EnumValue */
    protected $enumValue;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->enumValue = new EnumValue();
    }

    public function testProperties()
    {
        static::assertPropertyAccessors($this->enumValue, [
            ['id', 'testId'],
            ['label', 'test label'],
            ['isDefault', true],
            ['priority', 100],
        ]);
    }

    public function testToArray()
    {
        static::assertPropertyAccessors($this->enumValue, [
            ['id', 'testId'],
            ['label', 'test label'],
            ['isDefault', true],
            ['priority', 100],
        ]);
    }


    public function testFromToArray()
    {
        $array = [
            'id' => 'testId',
            'label' => 'test label',
            'isDefault' => true,
            'priority' => 100,
        ];

        $enumValue = (new EnumValue())
            ->setLabel('test label')
            ->setId('testId')
            ->setIsDefault(true)
            ->setPriority(100)
        ;

        static::assertEquals($enumValue, (new EnumValue())->fromArray($array));
        static::assertEquals($array, $enumValue->toArray($array));
    }
}
