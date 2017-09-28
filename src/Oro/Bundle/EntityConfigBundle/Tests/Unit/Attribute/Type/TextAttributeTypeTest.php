<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\TextAttributeType;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

class TextAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType()
    {
        return new TextAttributeType();
    }

    public function testGetType()
    {
        $this->assertEquals('text', $this->getAttributeType()->getType());
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider()
    {
        yield [
            'isSearchable' => true,
            'isFilterable' => true,
            'isSortable' => false
        ];
    }

    public function testGetSearchableValue()
    {
        $string = 'test';

        $this->assertSame(
            $string,
            $this->getAttributeType()
                ->getSearchableValue($this->attribute, new StubEnumValue('id', $string), $this->localization)
        );
    }

    public function testGetFilterableValue()
    {
        $string = 'test';

        $this->assertSame(
            $string,
            $this->getAttributeType()
                ->getFilterableValue($this->attribute, new StubEnumValue('id', $string), $this->localization)
        );
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
