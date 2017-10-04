<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\StringAttributeType;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

class StringAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType()
    {
        return new StringAttributeType();
    }

    public function testGetType()
    {
        $this->assertEquals('string', $this->getAttributeType()->getType());
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider()
    {
        yield [
            'isSearchable' => true,
            'isFilterable' => true,
            'isSortable' => true
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

    public function testGetSortableValue()
    {
        $string = 'test';

        $this->assertSame(
            $string,
            $this->getAttributeType()
                ->getSortableValue($this->attribute, new StubEnumValue('id', $string), $this->localization)
        );
    }
}
