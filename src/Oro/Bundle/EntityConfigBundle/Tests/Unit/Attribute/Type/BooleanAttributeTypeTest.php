<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\BooleanAttributeType;

class BooleanAttributeTypeTest extends AttributeTypeTestCase
{
    #[\Override]
    protected function getAttributeType(): AttributeTypeInterface
    {
        return new BooleanAttributeType();
    }

    #[\Override]
    public function configurationMethodsDataProvider(): array
    {
        return [
            ['isSearchable' => false, 'isFilterable' => true, 'isSortable' => true]
        ];
    }

    public function testGetSearchableValue(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getAttributeType()->getSearchableValue($this->attribute, true, $this->localization);
    }

    public function testGetFilterableValue(): void
    {
        $type = $this->getAttributeType();

        $this->assertSame(
            BooleanAttributeType::FALSE_VALUE,
            $type->getFilterableValue($this->attribute, false, $this->localization)
        );
        $this->assertSame(
            BooleanAttributeType::TRUE_VALUE,
            $type->getFilterableValue($this->attribute, true, $this->localization)
        );
    }

    public function testGetFilterableNull(): void
    {
        $this->assertSame(
            BooleanAttributeType::FALSE_VALUE,
            $this->getAttributeType()->getFilterableValue($this->attribute, null, $this->localization)
        );
    }

    public function testGetSortableValue(): void
    {
        $type = $this->getAttributeType();

        $this->assertSame(
            BooleanAttributeType::FALSE_VALUE,
            $type->getSortableValue($this->attribute, false, $this->localization)
        );
        $this->assertSame(
            BooleanAttributeType::TRUE_VALUE,
            $type->getSortableValue($this->attribute, true, $this->localization)
        );
    }
}
