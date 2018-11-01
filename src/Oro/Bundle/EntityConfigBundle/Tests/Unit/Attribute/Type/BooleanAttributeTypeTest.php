<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\BooleanAttributeType;

class BooleanAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType()
    {
        return new BooleanAttributeType();
    }

    public function testGetType()
    {
        $this->assertEquals('boolean', $this->getAttributeType()->getType());
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider()
    {
        yield [
            'isSearchable' => false,
            'isFilterable' => true,
            'isSortable' => true
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetSearchableValue()
    {
        $this->getAttributeType()->getSearchableValue($this->attribute, true, $this->localization);
    }

    public function testGetFilterableValue()
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

    public function testGetSortableValue()
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
