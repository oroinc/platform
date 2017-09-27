<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\IntegerAttributeType;

class IntegerAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType()
    {
        return new IntegerAttributeType('integer');
    }

    public function testGetType()
    {
        $this->assertEquals('integer', $this->getAttributeType()->getType());
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
        $this->assertSame(
            100,
            $this->getAttributeType()->getFilterableValue($this->attribute, '100 test', $this->localization)
        );
    }

    public function testGetSortableValue()
    {
        $this->assertSame(
            100,
            $this->getAttributeType()->getSortableValue($this->attribute, '100 test', $this->localization)
        );
    }
}
