<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\ManyToOneAttributeType;

class ManyToOneAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType()
    {
        return new ManyToOneAttributeType($this->entityNameResolver);
    }

    public function testGetType()
    {
        $this->assertEquals('manyToOne', $this->getAttributeType()->getType());
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
        $value = new \stdClass();

        $this->assertSame(
            'resolved stdClass name in de locale',
            $this->getAttributeType()->getSearchableValue($this->attribute, $value, $this->localization)
        );
    }

    public function testGetFilterableValue()
    {
        $value = new \stdClass();

        $this->assertSame(
            'resolved stdClass name in de locale',
            $this->getAttributeType()->getFilterableValue($this->attribute, $value, $this->localization)
        );
    }

    public function testGetSortableValue()
    {
        $value = new \stdClass();

        $this->assertSame(
            'resolved stdClass name in de locale',
            $this->getAttributeType()->getSortableValue($this->attribute, $value, $this->localization)
        );
    }
}
