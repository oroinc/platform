<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\OneToManyAttributeType;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

class OneToManyAttributeTypeTest extends AttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeType()
    {
        return new OneToManyAttributeType($this->entityNameResolver);
    }

    public function testGetType()
    {
        $this->assertEquals('oneToMany', $this->getAttributeType()->getType());
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
        $value1 = new \stdClass();
        $value2 = new StubEnumValue('id', 'enum');

        $this->assertSame(
            'resolved stdClass name in de locale ' .
            'resolved Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue name in de locale',
            $this->getAttributeType()->getSearchableValue($this->attribute, [$value1, $value2], $this->localization)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value must be an array or Traversable, [string] given
     */
    public function testGetSearchableValueTraversableException()
    {
        $this->getAttributeType()->getSearchableValue($this->attribute, '', $this->localization);
    }

    public function testGetFilterableValue()
    {
        $value1 = new \stdClass();
        $value2 = new StubEnumValue('id', 'enum');

        $this->assertSame(
            'resolved stdClass name in de locale ' .
            'resolved Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue name in de locale',
            $this->getAttributeType()->getFilterableValue($this->attribute, [$value1, $value2], $this->localization)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value must be an array or Traversable, [string] given
     */
    public function testGetFilterableValueTraversableException()
    {
        $this->getAttributeType()->getFilterableValue($this->attribute, '', $this->localization);
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
