<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\ManyToOneAttributeType;
use PHPUnit\Framework\MockObject\MockObject;

class ManyToOneAttributeTypeTest extends AttributeTypeTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
    }

    #[\Override]
    protected function getAttributeType(): AttributeTypeInterface
    {
        return new ManyToOneAttributeType($this->entityNameResolver, $this->doctrineHelper);
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
        $value = new \stdClass();

        $this->assertSame(
            'resolved stdClass name in de locale',
            $this->getAttributeType()->getSearchableValue($this->attribute, $value, $this->localization)
        );
    }

    public function testGetFilterableValue(): void
    {
        $value = new \stdClass();
        $filterableValue = 'filterable_value';

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($value), false)
            ->willReturn($filterableValue);

        $this->assertSame(
            $filterableValue,
            $this->getAttributeType()->getFilterableValue($this->attribute, $value, $this->localization)
        );
    }

    public function testGetFilterableValueNotObject(): void
    {
        $value = 'test_value';

        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->assertNull($this->getAttributeType()->getFilterableValue($this->attribute, $value, $this->localization));
    }

    public function testGetSortableValue(): void
    {
        $value = new \stdClass();

        $this->assertSame(
            'resolved stdClass name in de locale',
            $this->getAttributeType()->getSortableValue($this->attribute, $value, $this->localization)
        );
    }
}
