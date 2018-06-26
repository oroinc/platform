<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class AttributeGroupTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
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

    public function testCollections()
    {
        $collections = [
            ['labels', new LocalizedFallbackValue()],
            ['attributeRelations', new AttributeGroupRelation()],
        ];

        $entity = new AttributeGroup();
        $this->assertPropertyCollections($entity, $collections);
    }
}
