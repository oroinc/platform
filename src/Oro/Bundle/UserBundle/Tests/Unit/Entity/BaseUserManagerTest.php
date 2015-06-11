<?php

namespace Oro\Bundle\UserBundle\Tests\Entity;

use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;

class BaseUserManagerTest extends \PHPUnit_Framework_TestCase
{
    const USER_CLASS = 'Oro\Bundle\UserBundle\Entity\User';
    const TEST_NAME = 'Jack';
    const TEST_EMAIL = 'jack@jackmail.net';

    /**
     * @var User
     */
    protected $user;

    /**
     * @var BaseUserManager
     */
    protected $userManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $om;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $ef;

    protected function setUp()
    {
        if (!interface_exists('Doctrine\Common\Persistence\ObjectManager')) {
            $this->markTestSkipped('Doctrine Common has to be installed for this test to run.');
        }

        $this->ef = $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
        $class = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');

        $this->om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $this->om
            ->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->will($this->returnValue($this->repository));

        $this->om
            ->expects($this->any())
            ->method('getClassMetadata')
            ->with($this->equalTo(static::USER_CLASS))
            ->will($this->returnValue($class));

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->om));

        $class->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(static::USER_CLASS));

        $this->userManager = new BaseUserManager(static::USER_CLASS, $this->registry, $this->ef);
    }

    public function testGetClass()
    {
        $this->assertEquals(static::USER_CLASS, $this->userManager->getClass());
    }

    public function testCreateUser()
    {
        $this->assertInstanceof(static::USER_CLASS, $this->getUser());
    }

    public function testDeleteUser()
    {
        $user = $this->getUser();

        $this->om->expects($this->once())->method('remove')->with($this->equalTo($user));
        $this->om->expects($this->once())->method('flush');

        $this->userManager->deleteUser($user);
    }

    public function testUpdateUser()
    {
        $password = 'password';
        $encodedPassword = 'encodedPassword';

        $user = $this->getUser()
            ->setUsername(self::TEST_NAME)
            ->setEmail(self::TEST_EMAIL)
            ->setPlainPassword($password);

        $encoder = $this->getMock('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface');
        $encoder->expects($this->once())
            ->method('encodePassword')
            ->with($user->getPlainPassword(), $user->getSalt())
            ->will($this->returnValue($encodedPassword));

        $this->ef->expects($this->once())
            ->method('getEncoder')
            ->with($user)
            ->will($this->returnValue($encoder));

        $this->om->expects($this->once())->method('persist')->with($this->equalTo($user));
        $this->om->expects($this->once())->method('flush');

        $this->userManager->updateUser($user);

        $this->assertEquals(self::TEST_EMAIL, $user->getEmail());
        $this->assertEquals($encodedPassword, $user->getPassword());
    }

    public function testFindUserBy()
    {
        $crit = ['id' => 0];

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo($crit))
            ->will($this->returnValue([]));

        $this->userManager->findUserBy($crit);
    }

    public function testFindUsers()
    {
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue([]));

        $this->userManager->findUsers();
    }

    public function testFindUserByUsername()
    {
        $crit = ['username' => self::TEST_NAME];

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo($crit))
            ->will($this->returnValue([]));

        $this->userManager->findUserByUsernameOrEmail(self::TEST_NAME);
    }

    public function testFindUserByEmail()
    {
        $crit = ['email' => self::TEST_EMAIL];

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo($crit))
            ->will($this->returnValue([]));

        $this->userManager->findUserByUsernameOrEmail(self::TEST_EMAIL);
    }

    public function testFindUserByToken()
    {
        $crit = ['confirmationToken' => self::TEST_NAME];

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo($crit))
            ->will($this->returnValue([]));

        $this->userManager->findUserByConfirmationToken(self::TEST_NAME);
    }

    public function testReloadUser()
    {
        $user = $this->getUser();

        $this->om
            ->expects($this->once())
            ->method('refresh')
            ->with($this->equalTo($user));

        $this->userManager->reloadUser($user);
    }

    public function testRefreshUser()
    {
        $user = $this->getUser();
        $crit = ['username' => $user->getUsername()];

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo($crit))
            ->will($this->returnValue([]));

        $this->userManager->refreshUser($user);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Account is not supported
     */
    public function testRefreshUserNotSupported()
    {
        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $this->userManager->refreshUser($user);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Expected an instance of Oro\Bundle\UserBundle\Entity\UserInterface, but got
     */
    public function testRefreshUserNotOroUser()
    {
        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $userManager = new BaseUserManager(
            'Symfony\Component\Security\Core\User\UserInterface',
            $this->registry,
            $this->ef
        );

        $userManager->refreshUser($user);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUserByUsernameNotFound()
    {
        $crit = ['username' => self::TEST_NAME];

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo($crit))
            ->will($this->returnValue(null));

        $this->userManager->loadUserByUsername(self::TEST_NAME);
    }

    public function testLoadUserByUsername()
    {
        $user = $this->getUser();
        $crit = ['username' => self::TEST_NAME];

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo($crit))
            ->will($this->returnValue($user));

        $this->assertEquals($user, $this->userManager->loadUserByUsername(self::TEST_NAME));
    }

    public function testSupportsClass()
    {
        $this->assertTrue($this->userManager->supportsClass(static::USER_CLASS));
        $this->assertFalse($this->userManager->supportsClass('stdClass'));
    }

    protected function getUser()
    {
        return $this->userManager->createUser();
    }
}
