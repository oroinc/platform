<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributeFamilyTest extends \PHPUnit\Framework\TestCase
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
            ['labels', new LocalizedFallbackValue()]
        ];

        $entity = new AttributeFamily();
        $this->assertPropertyCollections($entity, $collections);
    }

    public function testAttributeGroupCollection()
    {
        $entity = new AttributeFamily();
        $group = (new AttributeGroup())->setCode('group_code');
        $group2 = (new AttributeGroup())->setCode('group_code');
        $this->setValue($group, 'id', 1);
        $this->setValue($group2, 'id', 2);

        $attributeGroups = new ArrayCollection(['group_code' => $group]);
        $entity->setAttributeGroups($attributeGroups);
        $this->assertEquals($attributeGroups, $entity->getAttributeGroups());

        $entity->addAttributeGroup($group);
        $this->assertEquals($group, $entity->getAttributeGroup('group_code'));
        $this->assertEquals($entity, $group->getAttributeFamily());

        $entity->addAttributeGroup($group2);
        $this->assertEquals($group2, $entity->getAttributeGroup('group_code'));

        $entity->removeAttributeGroup($group);
        $this->assertFalse($entity->getAttributeGroups()->contains($group));
    }

    public function testGetNotExistedAttributeGroup()
    {
        $entity = new AttributeFamily();
        $group = new AttributeGroup();
        $group->setCode('group_code');
        $entity->addAttributeGroup($group);

        $this->assertNull($entity->getAttributeGroup('group_code_2'));
    }

    public function testGetHash()
    {
        $group1 = $this->getEntity(AttributeGroup::class, ['id' => 1]);
        $group2 = $this->getEntity(AttributeGroup::class, ['id' => 2]);
        $attributeGroups = new ArrayCollection([$group1, $group2]);
        /** @var AttributeFamily $entity */
        $entity = $this->getEntity(
            AttributeFamily::class,
            [
                'id' => 1,
                'attribute_groups' => $attributeGroups,
            ]
        );

        $result[1] = [
            [
                'group' => 1,
                'attributes' => [],
                'visible' => true
            ],
            [
                'group' => 2,
                'attributes' => [],
                'visible' => true
            ],
        ];

        $this->assertEquals(md5(serialize($result)), $entity->getHash());
    }

    public function testToString()
    {
        /** @var AttributeFamily $entity */
        $entity = new AttributeFamily();
        $entity->setCode('default_family');
        $this->assertEquals('code:default_family', $entity->toString());
    }
}
