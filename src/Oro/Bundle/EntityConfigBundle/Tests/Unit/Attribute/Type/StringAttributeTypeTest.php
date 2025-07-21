<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\StringAttributeType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class StringAttributeTypeTest extends AttributeTypeTestCase
{
    #[\Override]
    protected function getAttributeType(): AttributeTypeInterface
    {
        return new StringAttributeType();
    }

    #[\Override]
    public function configurationMethodsDataProvider(): array
    {
        return [
            ['isSearchable' => true, 'isFilterable' => true, 'isSortable' => true]
        ];
    }

    public function testGetSearchableValue(): void
    {
        $string = 'test';

        $this->assertSame(
            $string,
            $this->getAttributeType()
                ->getSearchableValue(
                    $this->attribute,
                    new TestEnumValue('test_enum_code', $string, 'id', 1),
                    $this->localization
                )
        );
    }

    public function testGetFilterableValue(): void
    {
        $string = 'test';

        $this->assertSame(
            $string,
            $this->getAttributeType()
                ->getFilterableValue(
                    $this->attribute,
                    new TestEnumValue('test_enum_code', 'test', $string, 1),
                    $this->localization
                )
        );
    }

    public function testGetSortableValue(): void
    {
        $string = 'test';

        $this->assertSame(
            $string,
            $this->getAttributeType()
                ->getSortableValue(
                    $this->attribute,
                    new TestEnumValue('test_enum_code', 'test', $string, 1),
                    $this->localization
                )
        );
    }
}
