<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\IntegerAttributeType;

class IntegerAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType(): AttributeTypeInterface
    {
        return new IntegerAttributeType();
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider(): array
    {
        return [
            ['isSearchable' => false, 'isFilterable' => true, 'isSortable' => true]
        ];
    }

    public function testGetSearchableValue()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getAttributeType()->getSearchableValue($this->attribute, true, $this->localization);
    }

    public function testGetFilterableValue()
    {
        $this->assertSame(
            100,
            $this->getAttributeType()->getFilterableValue($this->attribute, '100 test', $this->localization)
        );
    }

    public function testGetFilterableNull()
    {
        $this->assertNull(
            $this->getAttributeType()->getFilterableValue($this->attribute, null, $this->localization)
        );
    }

    public function testGetSortableValue()
    {
        $this->assertSame(
            100,
            $this->getAttributeType()->getSortableValue($this->attribute, '100 test', $this->localization)
        );
    }

    public function testGetSortableValueNull()
    {
        $this->assertNull(
            $this->getAttributeType()->getSortableValue($this->attribute, null, $this->localization)
        );
    }
}
