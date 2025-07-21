<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\EnumAttributeType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class EnumAttributeTypeTest extends AttributeTypeTestCase
{
    #[\Override]
    protected function getAttributeType(): AttributeTypeInterface
    {
        return new EnumAttributeType();
    }

    #[\Override]
    public function configurationMethodsDataProvider(): array
    {
        return [
            ['isSearchable' => true, 'isFilterable' => true, 'isSortable' => true]
        ];
    }

    public function testGetSearchableValue(): void
    {
        $value = new TestEnumValue('test', 'name', 'id', 100);

        $this->assertEquals(
            'name',
            $this->getAttributeType()->getSearchableValue($this->attribute, $value, $this->localization)
        );
    }

    public function testGetSearchableValueForNull(): void
    {
        $this->assertNull($this->getAttributeType()->getSearchableValue($this->attribute, null, $this->localization));
    }

    public function testGetSearchableValueException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value must be instance of "Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface", "boolean" given'
        );

        $this->getAttributeType()->getSearchableValue($this->attribute, true, $this->localization);
    }

    public function testGetFilterableValue(): void
    {
        $value = new TestEnumValue('id', 'test', 'name', 100);

        $this->assertEquals(
            [$this->attribute->getFieldName() . '_enum.' . $value->getInternalId() => 1],
            $this->getAttributeType()->getFilterableValue($this->attribute, $value, $this->localization)
        );
    }

    public function testGetFilterableValueForNull(): void
    {
        $this->assertSame(
            [],
            $this->getAttributeType()->getFilterableValue($this->attribute, null, $this->localization)
        );
    }

    public function testGetFilterableValueException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value must be instance of "Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface", "boolean" given'
        );

        $this->getAttributeType()->getFilterableValue($this->attribute, true, $this->localization);
    }

    public function testGetSortableValue(): void
    {
        $value = new TestEnumValue('id', 'test', 'name', 100);

        $this->assertEquals(
            100,
            $this->getAttributeType()->getSortableValue($this->attribute, $value, $this->localization)
        );
    }

    public function testGetSortableValueForNull(): void
    {
        $this->assertNull($this->getAttributeType()->getSortableValue($this->attribute, null, $this->localization));
    }

    public function testGetSortableValueException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value must be instance of "Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface", "boolean" given'
        );

        $this->getAttributeType()->getSortableValue($this->attribute, true, $this->localization);
    }
}
