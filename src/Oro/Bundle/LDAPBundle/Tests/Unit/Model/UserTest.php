<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\Model;

use Oro\Bundle\LDAPBundle\Model\User;
use Oro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingUser;
use Oro\Bundle\UserBundle\Entity\Role;

class UserTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFromUser()
    {
        $roles = [
            new Role('role1'),
            new Role('role2'),
        ];

        $oroUser = new TestingUser();
        $oroUser
            ->setUsername('username')
            ->setPassword('password')
            ->setSalt('salt')
            ->setRoles($roles)
            ->setDn('dn');

        $user = User::createFromUser($oroUser);
        $this->assertEquals('username', $user->getUsername());
        $this->assertEquals('password', $user->getPassword());
        $this->assertEquals('salt', $user->getSalt());
        $this->assertEquals($roles, $user->getRoles());
        $this->assertEquals('dn', $user->getDn());
    }
}
