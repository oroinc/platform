<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Entity;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class ActivityOwnerTest extends TestCase
{
    private ActivityOwner $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = new ActivityOwner();
    }

    public function testGetId(): void
    {
        $this->assertNull($this->entity->getId());

        $value = 42;
        ReflectionUtil::setId($this->entity, $value);
        $this->assertSame($value, $this->entity->getId());
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($this->entity, $property, $value);
        $this->assertEquals($value, $accessor->getValue($this->entity, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['activity', new ActivityList()],
            ['user', new User()],
            ['organization', new Organization()],
        ];
    }

    /**
     * Test add and remove activity owner from ActivityList
     */
    public function testAddRemoveActivityOwner(): void
    {
        $activity = new ActivityList();
        $activity->setId(1);
        $organization = new Organization();

        $user1 = new User();
        $user1->setId(1);
        $user1->setFirstName('TestUserName1');
        $entity1 = new ActivityOwner();
        $entity1->setActivity($activity);
        $entity1->setUser($user1);
        $entity1->setOrganization($organization);
        $activity->addActivityOwner($entity1);

        $user2 = new User();
        $user1->setId(2);
        $user2->setFirstName('TestUserName2');
        $entity2 = new ActivityOwner();
        $entity2->setActivity($activity);
        $entity2->setUser($user2);
        $entity2->setOrganization($organization);
        $activity->addActivityOwner($entity2);

        $this->assertTrue($activity->getActivityOwners()->contains($entity1));
        $this->assertTrue($activity->getActivityOwners()->contains($entity2));

        $activity->removeActivityOwner($entity1);
        $activity->removeActivityOwner($entity2);
        $this->assertFalse($activity->getActivityOwners()->contains($entity1));
        $this->assertFalse($activity->getActivityOwners()->contains($entity2));
    }
}
