<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\DecimalAttributeType;

class DecimalAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType()
    {
        return new DecimalAttributeType('decimal');
    }

    public function testGetType()
    {
        $this->assertEquals('decimal', $this->getAttributeType()->getType());
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
        $this->getAttributeType()->getSearchableValue($this->attribute, 42.42, $this->localization);
    }

    public function testGetFilterableValue()
    {
        $this->assertSame(
            42.42,
            $this->getAttributeType()->getFilterableValue($this->attribute, '42.42 test', $this->localization)
        );
    }

    public function testGetSortableValue()
    {
        $this->assertSame(
            42.42,
            $this->getAttributeType()->getFilterableValue($this->attribute, '42.42 test', $this->localization)
        );
    }
}
