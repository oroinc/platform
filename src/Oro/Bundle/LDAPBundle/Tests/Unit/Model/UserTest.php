<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\Model;

use Oro\Bundle\LDAPBundle\Model\User;
use Oro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingUser;
use Oro\Bundle\UserBundle\Entity\Role;

class UserTest extends \PHPUnit_Framework_TestCase
{

    /** @var User */
    private $oroUser;

    /** @var Role[] */
    private $roles;

    public function setUp()
    {
        $this->roles = [
            new Role('role1'),
            new Role('role2'),
        ];

        $this->oroUser = new TestingUser();
        $this->oroUser
            ->setUsername('username')
            ->setPassword('password')
            ->setSalt('salt')
            ->setRoles($this->roles)
            ->setLdapMappings([
                1 => 'an example of user distinguished name in channel with id 1',
                40 => 'an example of user distinguished name in channel with id 40',
            ]);
    }

    public function testCreateFromUserWithMappingForChannel()
    {
        $user = User::createFromUser($this->oroUser, 40);
        $this->assertEquals('username', $user->getUsername());
        $this->assertEquals('password', $user->getPassword());
        $this->assertEquals('salt', $user->getSalt());
        $this->assertEquals($this->roles, $user->getRoles());
        $this->assertEquals('an example of user distinguished name in channel with id 40', $user->getDn());
    }

    public function testCreateFromUserWithoutMappingForChannel()
    {
        $user = User::createFromUser($this->oroUser, 25);
        $this->assertEquals('username', $user->getUsername());
        $this->assertEquals('password', $user->getPassword());
        $this->assertEquals('salt', $user->getSalt());
        $this->assertEquals($this->roles, $user->getRoles());
        $this->assertNull($user->getDn());
    }
}
