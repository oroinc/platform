<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\DateAttributeType;

class DateAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType()
    {
        return new DateAttributeType('date');
    }

    public function testGetType()
    {
        $this->assertEquals('date', $this->getAttributeType()->getType());
    }

    /**
     * {@inheritdoc}
     */
    public function configurationMethodsDataProvider()
    {
        yield [
            'isSearchable' => false,
            'isFilterable' => false,
            'isSortable' => true
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetSearchableValue()
    {
        $this->getAttributeType()->getSearchableValue($this->attribute, new \DateTime(), $this->localization);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetFilterableValue()
    {
        $this->getAttributeType()->getFilterableValue($this->attribute, new \DateTime(), $this->localization);
    }

    public function testGetSortableValue()
    {
        $date = new \DateTime('2017-01-01 12:00:00', new \DateTimeZone('America/Los_Angeles'));

        $this->assertEquals(
            $date,
            $this->getAttributeType()
                ->getSortableValue($this->attribute, $date, $this->localization)
        );
    }

    public function testGetSortableValueNullValue()
    {
        $this->assertNull($this->getAttributeType()->getSortableValue($this->attribute, null, $this->localization));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value must be instance of "DateTime", "stdClass" given
     */
    public function testGetSortableValueException()
    {
        $this->getAttributeType()->getSortableValue($this->attribute, new \stdClass(), $this->localization);
    }
}
