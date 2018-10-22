<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\EnumAttributeType;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

class EnumAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType()
    {
        return new EnumAttributeType();
    }

    public function testGetType()
    {
        $this->assertEquals('enum', $this->getAttributeType()->getType());
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
        $value = new StubEnumValue('id', 'name', 100);

        $this->assertEquals(
            'name',
            $this->getAttributeType()->getSearchableValue($this->attribute, $value, $this->localization)
        );
    }

    public function testGetSearchableValueForNull()
    {
        $this->assertNull($this->getAttributeType()->getSearchableValue($this->attribute, null, $this->localization));
    }

    /**
     * @codingStandardsIgnoreStart
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value must be instance of "Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue", "boolean" given
     *
     * @codingStandardsIgnoreEnd
     */
    public function testGetSearchableValueException()
    {
        $this->getAttributeType()->getSearchableValue($this->attribute, true, $this->localization);
    }

    public function testGetFilterableValue()
    {
        $value = new StubEnumValue('id', 'name', 100);

        $this->assertEquals(
            [$this->attribute->getFieldName() . '_' . $value->getId() => 1],
            $this->getAttributeType()->getFilterableValue($this->attribute, $value, $this->localization)
        );
    }

    public function testGetFilterableValueForNull()
    {
        $this->assertSame(
            [],
            $this->getAttributeType()->getFilterableValue($this->attribute, null, $this->localization)
        );
    }

    /**
     * @codingStandardsIgnoreStart
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value must be instance of "Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue", "boolean" given
     *
     * @codingStandardsIgnoreEnd
     */
    public function testGetFilterableValueException()
    {
        $this->getAttributeType()->getFilterableValue($this->attribute, true, $this->localization);
    }

    public function testGetSortableValue()
    {
        $value = new StubEnumValue('id', 'name', 100);

        $this->assertEquals(
            100,
            $this->getAttributeType()->getSortableValue($this->attribute, $value, $this->localization)
        );
    }

    public function testGetSortableValueForNull()
    {
        $this->assertNull($this->getAttributeType()->getSortableValue($this->attribute, null, $this->localization));
    }

    /**
     * @codingStandardsIgnoreStart
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value must be instance of "Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue", "boolean" given
     *
     * @codingStandardsIgnoreEnd
     */
    public function testGetSortableValueException()
    {
        $this->getAttributeType()->getSortableValue($this->attribute, true, $this->localization);
    }
}
