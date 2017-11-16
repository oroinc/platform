<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\FileAttributeType;

class FileAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType()
    {
        return new FileAttributeType('file');
    }

    public function testGetType()
    {
        $this->assertEquals('file', $this->getAttributeType()->getType());
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider()
    {
        yield [
            'isSearchable' => false,
            'isFilterable' => false,
            'isSortable' => false
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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetFilterableValue()
    {
        $this->getAttributeType()->getFilterableValue($this->attribute, true, $this->localization);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetSortableValue()
    {
        $this->getAttributeType()->getSortableValue($this->attribute, true, $this->localization);
    }
}
