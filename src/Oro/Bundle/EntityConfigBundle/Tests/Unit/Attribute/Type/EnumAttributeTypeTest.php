<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\EnumAttributeType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class EnumAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType(): AttributeTypeInterface
    {
        return new EnumAttributeType();
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider(): array
    {
        return [
            ['isSearchable' => true, 'isFilterable' => true, 'isSortable' => true]
        ];
    }

    public function testGetSearchableValue()
    {
        $value = new TestEnumValue('id', 'name', 100);

        $this->assertEquals(
            'name',
            $this->getAttributeType()->getSearchableValue($this->attribute, $value, $this->localization)
        );
    }

    public function testGetSearchableValueForNull()
    {
        $this->assertNull($this->getAttributeType()->getSearchableValue($this->attribute, null, $this->localization));
    }

    public function testGetSearchableValueException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value must be instance of "Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue", "boolean" given'
        );

        $this->getAttributeType()->getSearchableValue($this->attribute, true, $this->localization);
    }

    public function testGetFilterableValue()
    {
        $value = new TestEnumValue('id', 'name', 100);

        $this->assertEquals(
            [$this->attribute->getFieldName() . '_enum.' . $value->getId() => 1],
            $this->getAttributeType()->getFilterableValue($this->attribute, $value, $this->localization)
        );
    }

    public function testGetFilterableValueForNull()
    {
        $this->assertSame(
            [],
            $this->getAttributeType()->getFilterableValue($this->attribute, null, $this->localization)
        );
    }

    public function testGetFilterableValueException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value must be instance of "Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue", "boolean" given'
        );

        $this->getAttributeType()->getFilterableValue($this->attribute, true, $this->localization);
    }

    public function testGetSortableValue()
    {
        $value = new TestEnumValue('id', 'name', 100);

        $this->assertEquals(
            100,
            $this->getAttributeType()->getSortableValue($this->attribute, $value, $this->localization)
        );
    }

    public function testGetSortableValueForNull()
    {
        $this->assertNull($this->getAttributeType()->getSortableValue($this->attribute, null, $this->localization));
    }

    public function testGetSortableValueException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value must be instance of "Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue", "boolean" given'
        );

        $this->getAttributeType()->getSortableValue($this->attribute, true, $this->localization);
    }
}
