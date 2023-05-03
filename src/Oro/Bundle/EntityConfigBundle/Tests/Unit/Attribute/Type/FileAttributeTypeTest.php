<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\FileAttributeType;

class FileAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType(): AttributeTypeInterface
    {
        return new FileAttributeType();
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider(): array
    {
        return [
            ['isSearchable' => false, 'isFilterable' => false, 'isSortable' => false]
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
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getAttributeType()->getFilterableValue($this->attribute, true, $this->localization);
    }

    public function testGetSortableValue()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getAttributeType()->getSortableValue($this->attribute, true, $this->localization);
    }
}
