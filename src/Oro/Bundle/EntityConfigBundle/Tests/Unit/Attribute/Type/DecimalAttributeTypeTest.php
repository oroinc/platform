<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\DecimalAttributeType;

class DecimalAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType(): AttributeTypeInterface
    {
        return new DecimalAttributeType();
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

        $this->getAttributeType()->getSearchableValue($this->attribute, 42.42, $this->localization);
    }

    public function testGetFilterableValue()
    {
        $this->assertSame(
            42.42,
            $this->getAttributeType()->getFilterableValue($this->attribute, '42.42 test', $this->localization)
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
            42.42,
            $this->getAttributeType()->getSortableValue($this->attribute, '42.42 test', $this->localization)
        );
    }

    public function testGetSortableValueNull()
    {
        $this->assertNull(
            $this->getAttributeType()->getSortableValue($this->attribute, null, $this->localization)
        );
    }
}
