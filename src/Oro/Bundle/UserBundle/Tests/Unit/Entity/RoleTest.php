<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\RoleStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function testCreate(): void
    {
        $strRole = 'foo';
        $role = new Role($strRole);

        $this->assertEquals($strRole, $role->getLabel());
        $this->assertEquals($strRole, $role->getRole());
    }

    public function testRole(): void
    {
        $role = new Role();

        $this->assertEmpty($role->getId());
        $this->assertEmpty($role->getRole());

        $role->setRole('foo');

        $this->assertStringStartsWith('ROLE_FOO', $role->getRole());
        $this->assertEquals(Role::PREFIX_ROLE, $role->getPrefix());
        $this->assertStringStartsWith(Role::PREFIX_ROLE, (string)$role);
        $this->assertMatchesRegularExpression('/_[[:upper:]\d]{13}/', substr($role->getRole(), strrpos($role, '_')));
    }

    public function testLabel(): void
    {
        $role = new Role();
        $label = 'Test role';

        $this->assertEmpty($role->getLabel());

        $role->setLabel($label);

        $this->assertEquals($label, $role->getLabel());
        $this->assertNotEquals($label, (string)$role);
    }

    public function testClone(): void
    {
        $role = new Role();
        ReflectionUtil::setId($role, 1);

        $copy = clone $role;
        $this->assertEmpty($copy->getId());
    }

    public function testSerialization(): void
    {
        $organization = new Organization();
        $isEnabled = true;
        $name = 'Organization';
        $organization->setEnabled($isEnabled);
        $organization->setName($name);

        $firstRole = new RoleStub();
        $firstRole->setOrganization($organization);

        $secondRole = new RoleStub();

        $serializedString = $firstRole->__serialize();
        $secondRole->__unserialize($serializedString);
        /** @var Organization $unserializedOrganization */
        $unserializedOrganization = $secondRole->getOrganization();

        $this->assertInstanceOf(Organization::class, $unserializedOrganization);
        $this->assertEquals($isEnabled, $unserializedOrganization->isEnabled());
        $this->assertEquals($name, $unserializedOrganization->getName());
    }

    public function testSerializationWithoutOrganization(): void
    {
        $firstRole = new Role();
        $label = 'Label';
        $role = 'ROLE_ID';
        $firstRole->setRole($role);
        $firstRole->setLabel($label);

        $secondRole = new Role();

        $serializedString = $firstRole->__serialize();
        $secondRole->__unserialize($serializedString);

        self::assertEquals($label, $secondRole->getLabel());
        self::assertStringContainsString($role, $secondRole->getRole());
    }
}
