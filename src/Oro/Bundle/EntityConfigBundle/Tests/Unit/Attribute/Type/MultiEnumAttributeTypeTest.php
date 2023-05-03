<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\MultiEnumAttributeType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class MultiEnumAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType(): AttributeTypeInterface
    {
        return new MultiEnumAttributeType();
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider(): array
    {
        return [
            ['isSearchable' => true, 'isFilterable' => true, 'isSortable' => false]
        ];
    }

    public function testGetSearchableValue()
    {
        $value1 = new TestEnumValue('id1', 'name1', 101);
        $value2 = new TestEnumValue('id2', 'name2', 102);

        $this->assertSame(
            'name1 name2',
            $this->getAttributeType()->getSearchableValue($this->attribute, [$value1, $value2], $this->localization)
        );
    }

    public function testGetSearchableValueTraversableException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be an array or Traversable, [string] given');

        $this->getAttributeType()->getSearchableValue($this->attribute, '', $this->localization);
    }

    public function testGetSearchableValueValueException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value must be instance of "Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue", "integer" given'
        );

        $this->getAttributeType()->getSearchableValue($this->attribute, [42], $this->localization);
    }

    public function testGetFilterableValue()
    {
        $value1 = new TestEnumValue('id1', 'name1', 101);
        $value2 = new TestEnumValue('id2', 'name2', 102);

        $this->assertSame(
            [
                self::FIELD_NAME . '_enum.id1' => 1,
                self::FIELD_NAME . '_enum.id2' => 1
            ],
            $this->getAttributeType()->getFilterableValue($this->attribute, [$value1, $value2], $this->localization)
        );
    }

    public function testGetFilterableValueTraversableException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be an array or Traversable, [string] given');

        $this->getAttributeType()->getFilterableValue($this->attribute, '', $this->localization);
    }

    public function testGetFilterableValueValueException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value must be instance of "Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue", "integer" given'
        );

        $this->getAttributeType()->getFilterableValue($this->attribute, [42], $this->localization);
    }

    public function testGetSortableValue()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getAttributeType()->getSortableValue($this->attribute, true, $this->localization);
    }
}
