<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\UserBundle\Security\UserProvider;

class UserProviderTest extends \PHPUnit_Framework_TestCase
{
    const USER_CLASS = 'Oro\Bundle\UserBundle\Entity\User';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $userManager;

    /**
     * @var UserProvider
     */
    private $userProvider;

    protected function setUp()
    {
        if (!interface_exists('Doctrine\Common\Persistence\ObjectManager')) {
            $this->markTestSkipped('Doctrine Common has to be installed for this test to run.');
        }

        $this->userManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\BaseUserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->userManager
            ->expects($this->any())
            ->method('getClass')
            ->will($this->returnValue(static::USER_CLASS));

        $this->userProvider = new UserProvider($this->userManager);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUserByInvalidUsername()
    {
        $this->userManager
            ->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with($this->equalTo('foobar'))
            ->will($this->returnValue(null));

        $this->userProvider->loadUserByUsername('foobar');
    }

    public function testRefreshUserBy()
    {
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->setMethods(array('getId'))
            ->getMock();

        $refreshedUser = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $this->userManager
            ->expects($this->once())
            ->method('refreshUser')
            ->with($user)
            ->will($this->returnValue($refreshedUser));

        $this->assertSame($refreshedUser, $this->userProvider->refreshUser($user));
    }

    public function testSupportsClass()
    {
        $this->assertTrue($this->userProvider->supportsClass(static::USER_CLASS));
    }
}
