<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\UserBundle\Entity\User;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributeFamilyTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 777],
            ['code', 'some_code'],
            ['entityClass', 'SomeClass'],
            ['isEnabled', true],
            ['owner', new User()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()]
        ];

        $entity = new AttributeFamily();
        $this->assertPropertyAccessors($entity, $properties);
    }

    public function testCollections()
    {
        $collections = [
            ['labels', new LocalizedFallbackValue()],
            ['attributeGroups', new AttributeGroup()]
        ];

        $entity = new AttributeFamily();
        $this->assertPropertyCollections($entity, $collections);

        $attributeGroups = new ArrayCollection([new AttributeGroup()]);
        $entity->setAttributeGroups($attributeGroups);
        $this->assertEquals($attributeGroups, $entity->getAttributeGroups());
    }

    public function testToString()
    {
        $group1 = $this->getEntity(AttributeGroup::class, ['id' => 1]);
        $group2 = $this->getEntity(AttributeGroup::class, ['id' => 2]);
        $attributeGroups = new ArrayCollection([$group1, $group2]);
        $entity = $this->getEntity(AttributeFamily::class, [
            'id' => 1,
            'attribute_groups' => $attributeGroups,
        ]);

        $result[1] = [
            [
                'group' => 1,
                'attributes' => []
            ],
            [
                'group' => 2,
                'attributes' => []
            ],
        ];

        $this->assertEquals(md5(serialize($result)), $entity->toString());
    }
}
