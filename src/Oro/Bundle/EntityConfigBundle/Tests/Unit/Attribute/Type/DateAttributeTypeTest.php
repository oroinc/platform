<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\DateAttributeType;

class DateAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType(): AttributeTypeInterface
    {
        return new DateAttributeType();
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider(): array
    {
        return [
            ['isSearchable' => false, 'isFilterable' => false, 'isSortable' => true]
        ];
    }

    public function testGetSearchableValue()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getAttributeType()->getSearchableValue($this->attribute, new \DateTime(), $this->localization);
    }

    public function testGetFilterableValue()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getAttributeType()->getFilterableValue($this->attribute, new \DateTime(), $this->localization);
    }

    public function testGetSortableValue()
    {
        $date = new \DateTime('2017-01-01 12:00:00', new \DateTimeZone('America/Los_Angeles'));

        $this->assertEquals(
            $date,
            $this->getAttributeType()->getSortableValue($this->attribute, $date, $this->localization)
        );
    }

    public function testGetSortableValueNullValue()
    {
        $this->assertNull($this->getAttributeType()->getSortableValue($this->attribute, null, $this->localization));
    }

    public function testGetSortableValueException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be instance of "DateTime", "stdClass" given');

        $this->getAttributeType()->getSortableValue($this->attribute, new \stdClass(), $this->localization);
    }
}
