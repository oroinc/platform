<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class AttributeGroupTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 777],
            ['attributeFamily', new AttributeFamily()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['updatedAtSet', true]
        ];

        $entity = new AttributeGroup();
        $this->assertPropertyAccessors($entity, $properties);
    }

    public function testCollections(): void
    {
        $collections = [
            ['labels', new LocalizedFallbackValue()],
            ['attributeRelations', new AttributeGroupRelation()],
        ];

        $entity = new AttributeGroup();
        $this->assertPropertyCollections($entity, $collections);
    }

    public function testSetAttributeRelations(): void
    {
        $entity = new AttributeGroup();
        $collection = $this->createMock(ArrayCollection::class);

        $entity->setAttributeRelations($collection);
        $this->assertSame($collection, $entity->getAttributeRelations());
    }
}
