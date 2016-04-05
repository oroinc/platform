<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;

class RoleTest extends \PHPUnit_Framework_TestCase
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
        ReflectionUtil::setId($role, 1);

        $copy = clone $role;
        $this->assertEmpty($copy->getId());
    }
}
