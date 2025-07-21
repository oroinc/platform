<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class AttributeFamilyTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 777],
            ['code', 'some_code'],
            ['entityClass', 'SomeClass'],
            ['isEnabled', true],
            ['owner', new Organization()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()]
        ];

        $entity = new AttributeFamily();
        $this->assertPropertyAccessors($entity, $properties);
    }

    public function testCollections(): void
    {
        $collections = [
            ['labels', new LocalizedFallbackValue()]
        ];

        $entity = new AttributeFamily();
        $this->assertPropertyCollections($entity, $collections);
    }

    public function testAttributeGroupCollection(): void
    {
        $entity = new AttributeFamily();
        $group = new AttributeGroup();
        ReflectionUtil::setId($group, 1);
        $group->setCode('group_code');
        $group2 = new AttributeGroup();
        ReflectionUtil::setId($group2, 2);
        $group2->setCode('group_code');

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

    public function testGetNotExistedAttributeGroup(): void
    {
        $entity = new AttributeFamily();
        $group = new AttributeGroup();
        $group->setCode('group_code');
        $entity->addAttributeGroup($group);

        $this->assertNull($entity->getAttributeGroup('group_code_2'));
    }

    public function testGetHash(): void
    {
        $group1 = new AttributeGroup();
        ReflectionUtil::setId($group1, 1);
        $groupRelation1 = new AttributeGroupRelation();
        ReflectionUtil::setId($groupRelation1, 1);
        $groupRelation2 = new AttributeGroupRelation();
        ReflectionUtil::setId($groupRelation2, 2);
        $group1->addAttributeRelation($groupRelation1);
        $group1->addAttributeRelation($groupRelation2);

        $group2 = new AttributeGroup();
        ReflectionUtil::setId($group2, 2);

        $entity = new AttributeFamily();
        ReflectionUtil::setId($entity, 1);
        $entity->setAttributeGroups(new ArrayCollection([$group1, $group2]));

        $result[1] = [
            [
                'group' => 1,
                'attributes' => [1, 2],
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

    public function testToString(): void
    {
        $entity = new AttributeFamily();
        $entity->setCode('default_family');
        $this->assertEquals('code:default_family', $entity->toString());
    }
}
