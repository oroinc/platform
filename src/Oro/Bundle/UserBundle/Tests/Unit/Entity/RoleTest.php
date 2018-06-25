<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\RoleStub;

class RoleTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $strRole = 'foo';
        $role = new Role($strRole);

        $this->assertEquals($strRole, $role->getLabel());
        $this->assertEquals($strRole, $role->getRole());
    }

    public function testRole()
    {
        $role = new Role();

        $this->assertEmpty($role->getId());
        $this->assertEmpty($role->getRole());

        $role->setRole('foo');

        $this->assertStringStartsWith('ROLE_FOO', $role->getRole());
        $this->assertEquals(Role::PREFIX_ROLE, $role->getPrefix());
    }

    public function testLabel()
    {
        $role = new Role();
        $label = 'Test role';

        $this->assertEmpty($role->getLabel());

        $role->setLabel($label);

        $this->assertEquals($label, $role->getLabel());
        $this->assertEquals($label, (string)$role);
    }

    public function testClone()
    {
        $role = new Role();

        $class = new \ReflectionClass($role);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($role, 1);

        $copy = clone $role;
        $this->assertEmpty($copy->getId());
    }

    public function testSerialization()
    {
        $organization = new Organization();
        $isEnabled    = true;
        $name         = 'Organization';
        $organization->setEnabled($isEnabled);
        $organization->setName($name);

        $firstRole = new RoleStub();
        $firstRole->setOrganization($organization);

        $secondRole = new RoleStub();

        $serializedString = $firstRole->serialize();
        $secondRole->unserialize($serializedString);
        /** @var Organization $unserializedOrganization */
        $unserializedOrganization = $secondRole->getOrganization();
        
        $this->assertInstanceOf(Organization::class, $unserializedOrganization);
        $this->assertEquals($isEnabled, $unserializedOrganization->isEnabled());
        $this->assertEquals($name, $unserializedOrganization->getName());
    }

    public function testSerializationWithoutOrganization()
    {
        $firstRole = new Role();
        $label     = 'Label';
        $role      = 'ROLE_ID';
        $firstRole->setRole($role);
        $firstRole->setLabel($label);

        $secondRole = new Role();

        $serializedString = $firstRole->serialize();
        $secondRole->unserialize($serializedString);

        $this->assertEquals($label, $secondRole->getLabel());
        $this->assertContains($role, $secondRole->getRole());
    }
}
