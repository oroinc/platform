<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class GroupTest extends TestCase
{
    private const TEST_ROLE = 'ROLE_FOO';

    private Group $group;

    #[\Override]
    protected function setUp(): void
    {
        $this->group = new Group();
    }

    public function testGroup(): void
    {
        $name = 'Users';

        $this->assertEmpty($this->group->getId());
        $this->assertEmpty($this->group->getName());

        $this->group->setName($name);

        $this->assertEquals($name, $this->group->getName());
    }

    public function testGetRoleLabelsAsString(): void
    {
        $roleFoo = new Role('ROLE_FOO');
        $roleFoo->setLabel('Role foo');
        $this->group->addRole($roleFoo);

        $roleBar = new Role('ROLE_BAR');
        $roleBar->setLabel('Role bar');
        $this->group->addRole($roleBar);

        $this->assertEquals(
            'Role foo, Role bar',
            $this->group->getRoleLabelsAsString()
        );
    }

    public function testHasRoleWithStringArgument(): void
    {
        $role = new Role(self::TEST_ROLE);

        $this->assertFalse($this->group->hasRole(self::TEST_ROLE));
        $this->group->addRole($role);
        $this->assertTrue($this->group->hasRole(self::TEST_ROLE));
    }

    public function testHasRoleWithObjectArgument(): void
    {
        $role = new Role(self::TEST_ROLE);

        $this->assertFalse($this->group->hasRole($role));
        $this->group->addRole($role);
        $this->assertTrue($this->group->hasRole($role));
    }

    public function testHasRoleThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$role must be an instance of Oro\Bundle\UserBundle\Entity\Role or a string');

        $this->group->hasRole(new \stdClass());
    }

    public function testRemoveRoleWithStringArgument(): void
    {
        $role = new Role(self::TEST_ROLE);
        $this->group->addRole($role);

        $this->assertTrue($this->group->hasRole($role));
        $this->group->removeRole(self::TEST_ROLE);
        $this->assertFalse($this->group->hasRole($role));
    }

    public function testRemoveRoleWithObjectArgument(): void
    {
        $role = new Role(self::TEST_ROLE);
        $this->group->addRole($role);

        $this->assertTrue($this->group->hasRole($role));
        $this->group->removeRole($role);
        $this->assertFalse($this->group->hasRole($role));
    }

    public function testRemoveRoleThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$role must be an instance of Oro\Bundle\UserBundle\Entity\Role or a string');

        $this->group->removeRole(new \stdClass());
    }

    public function testSetRolesWithArrayArgument(): void
    {
        $roles = [new Role(self::TEST_ROLE)];
        $this->assertCount(0, $this->group->getRoles());
        $this->group->setRoles($roles);
        $this->assertEquals($roles, $this->group->getRoles()->toArray());
    }

    public function testSetRolesWithCollectionArgument(): void
    {
        $roles = new ArrayCollection([new Role(self::TEST_ROLE)]);
        $this->assertCount(0, $this->group->getRoles());
        $this->group->setRoles($roles);
        $this->assertEquals($roles->toArray(), $this->group->getRoles()->toArray());
    }

    public function testSetRolesThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '$roles must be an instance of Doctrine\Common\Collections\Collection or an array'
        );

        $this->group->setRoles('roles');
    }

    public function testOwners(): void
    {
        $entity = $this->group;
        $businessUnit = new BusinessUnit();

        $this->assertEmpty($entity->getOwner());

        $entity->setOwner($businessUnit);

        $this->assertEquals($businessUnit, $entity->getOwner());
    }

    public function testOrganization(): void
    {
        $entity = new Group();
        $organization = new Organization();

        $this->assertNull($entity->getOrganization());
        $entity->setOrganization($organization);
        $this->assertSame($organization, $entity->getOrganization());
    }
}
