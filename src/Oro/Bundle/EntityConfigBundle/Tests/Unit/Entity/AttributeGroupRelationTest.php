<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class AttributeGroupRelationTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 777],
            ['entityConfigFieldId', 111],
            ['attributeGroup', new AttributeGroup()],
        ];

        $entity = new AttributeGroupRelation();
        $this->assertPropertyAccessors($entity, $properties);
    }
}
