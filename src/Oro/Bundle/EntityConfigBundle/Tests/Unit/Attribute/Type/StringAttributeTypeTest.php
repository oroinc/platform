<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\StringAttributeType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class StringAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType(): AttributeTypeInterface
    {
        return new StringAttributeType();
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider(): array
    {
        return [
            ['isSearchable' => true, 'isFilterable' => true, 'isSortable' => true]
        ];
    }

    public function testGetSearchableValue()
    {
        $string = 'test';

        $this->assertSame(
            $string,
            $this->getAttributeType()
                ->getSearchableValue($this->attribute, new TestEnumValue('id', $string), $this->localization)
        );
    }

    public function testGetFilterableValue()
    {
        $string = 'test';

        $this->assertSame(
            $string,
            $this->getAttributeType()
                ->getFilterableValue($this->attribute, new TestEnumValue('id', $string), $this->localization)
        );
    }

    public function testGetSortableValue()
    {
        $string = 'test';

        $this->assertSame(
            $string,
            $this->getAttributeType()
                ->getSortableValue($this->attribute, new TestEnumValue('id', $string), $this->localization)
        );
    }
}
