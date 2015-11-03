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
