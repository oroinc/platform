<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class AttributeFamilyTest extends \PHPUnit_Framework_TestCase
{
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
}
