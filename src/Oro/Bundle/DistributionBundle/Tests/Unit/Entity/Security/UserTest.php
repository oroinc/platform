<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Entity\Security;


use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DistributionBundle\Entity\Security\User;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\ReflectionHelperTrait;

class UserTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionHelperTrait;

    /**
     * @test
     */
    public function shouldImplementAdvancedUserInterface()
    {
        $this->assertImplementsInterface(
            'Symfony\Component\Security\Core\User\AdvancedUserInterface',
            'Oro\Bundle\DistributionBundle\Entity\Security\User'
        );
    }

    /**
     * @test
     */
    public function shouldImplementSerializableInterface()
    {
        $this->assertImplementsInterface(
            'Serializable',
            'Oro\Bundle\DistributionBundle\Entity\Security\User'
        );
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutArgs()
    {
        new User();
    }

    /**
     * @test
     */
    public function shouldReturnUsername()
    {
        $user = new User;
        $this->writeAttribute($user, 'username', $username = 'MyUsername');

        $this->assertEquals($username, $user->getUsername());
    }

    /**
     * @test
     */
    public function shouldReturnPassword()
    {
        $user = new User;
        $this->writeAttribute($user, 'password', $password = 'password');

        $this->assertEquals($password, $user->getPassword());
    }

    /**
     * @test
     */
    public function shouldReturnSalt()
    {
        $user = new User;
        $this->writeAttribute($user, 'salt', $salt = 'mysalt');

        $this->assertEquals($salt, $user->getSalt());
    }

    /**
     * @test
     */
    public function shouldReturnRolesAccordingOnlyRoles()
    {
        $user = new User;
        $roles = ['role1', 'role2'];
        $this->writeAttribute($user, 'roles', new ArrayCollection($roles));

        $this->assertEquals($roles, $user->getRoles());
    }

    /**
     * @test
     */
    public function shouldReturnRolesAccordingRolesAndGroups()
    {
        $user = new User;
        $roles = ['role1', 'role2'];
        $group1 = $this->createGroupMock(['role1', 'role3']);
        $group2 = $this->createGroupMock(['role2', 'role4']);
        $this->writeAttribute($user, 'roles', new ArrayCollection($roles));
        $this->writeAttribute($user, 'groups', new ArrayCollection([$group1, $group2]));

        $this->assertEquals(['role1', 'role2', 'role3', 'role4'], $user->getRoles());
    }

    /**
     * @test
     */
    public function mustReturnTrueForAccountNonExpired()
    {
        $user = new User;
        $this->assertTrue($user->isAccountNonExpired());
    }

    /**
     * @test
     */
    public function mustReturnTrueForCredentialsNonExpired()
    {
        $user = new User;
        $this->assertTrue($user->isCredentialsNonExpired());
    }

    /**
     * @test
     */
    public function shouldReturnEnabledStatus()
    {
        $user = new User;

        $this->writeAttribute($user, 'enabled', true);
        $this->assertTrue($user->isEnabled());

        $this->writeAttribute($user, 'enabled', false);
        $this->assertFalse($user->isEnabled());
    }

    /**
     * @test
     */
    public function shouldReturnAccountNonLockedAccordingEnabledStatus()
    {
        $user = new User;

        $this->writeAttribute($user, 'enabled', true);
        $this->assertTrue($user->isAccountNonLocked());

        $this->writeAttribute($user, 'enabled', false);
        $this->assertFalse($user->isAccountNonLocked());
    }

    /**
     * @test
     */
    public function shouldSerialize()
    {
        $user = new User;
        $this->writeAttribute($user, 'password', 'password');
        $this->writeAttribute($user, 'salt', 'salt');
        $this->writeAttribute($user, 'username', 'username');
        $this->writeAttribute($user, 'enabled', true);
        $this->writeAttribute($user, 'id', 777);

        return $user->serialize();
    }

    /**
     * @test
     *
     * @depends shouldSerialize
     */
    public function shouldUnserializeData($data)
    {
        $user = new User();
        $user->unserialize($data);

        $this->assertEquals('password', $user->getPassword());
        $this->assertEquals('salt', $user->getSalt());
        $this->assertEquals('username', $user->getUsername());
        $this->assertTrue($user->isEnabled());
    }

    /**
     * @test
     */
    public function shouldDoNothingWhenEraseCredentials()
    {
        $user = new User;
        $user->eraseCredentials();
    }

    /**
     * @param array $roles
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createGroupMock($roles)
    {
        $group = $this->getMock('Oro\Bundle\DistributionBundle\Entity\Security\Group');
        $group->expects($this->any())
            ->method('getRoles')
            ->will($this->returnValue($roles));

        return $group;
    }
}
