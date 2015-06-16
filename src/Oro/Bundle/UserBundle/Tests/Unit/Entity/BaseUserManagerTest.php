<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

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
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $om;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EncoderFactoryInterface
     */
    protected $ef;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ClassMetadata
     */
    protected $metadata;

    protected function setUp()
    {
        if (!interface_exists('Doctrine\Common\Persistence\ObjectManager')) {
            $this->markTestSkipped('Doctrine Common has to be installed for this test to run.');
        }

        $this->ef = $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');

        $this->om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $this->om
            ->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->will($this->returnValue($this->repository));

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->om));

        $this->metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadata->expects($this->any())->method('getName')->willReturn(static::USER_CLASS);
        $this->om->expects($this->any())->method('getClassMetadata')->willReturn($this->metadata);

        $this->userManager = new BaseUserManager(static::USER_CLASS, $this->registry, $this->ef);
    }

    public function testGetClass()
    {
        $this->assertEquals(static::USER_CLASS, $this->userManager->getClass());
    }

    public function testCreateUser()
    {
        $this->assertInstanceOf(static::USER_CLASS, $this->getUser());
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

        $user = $this->getUser(true);
        $user->setUsername(self::TEST_NAME);
        $user->setEmail(self::TEST_EMAIL);
        $user->setPlainPassword($password);

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

    /**
     * @param bool $withRole
     *
     * @return User
     */
    protected function getUser($withRole = false)
    {
        $user = $this->userManager->createUser();
        if ($withRole) {
            $role = new Role($user->getDefaultRole());
            $user->addRole($role);
        }

        return $user;
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Default user role not found
     */
    public function testUpdateUserUnsupported()
    {
        $user = $this->getUser();

        $this->metadata->expects($this->once())->method('getAssociationTargetClass')
            ->willReturn('Symfony\Component\Security\Core\Role\RoleInterface');
        $this->om->expects($this->never())
            ->method('persist')
            ->with($this->equalTo($user));
        $this->om->expects($this->never())
            ->method('flush');

        $this->userManager->updateUser($user);
    }

    public function testUpdateUserWithRoles()
    {
        $password = 'password';
        $encodedPassword = 'encodedPassword';
        $email = 'test@test.com';

        $user = $this->getUser();
        $user
            ->setUsername($email)
            ->setEmail($email)
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

        $this->metadata->expects($this->once())->method('getAssociationTargetClass')
            ->willReturn('Symfony\Component\Security\Core\Role\RoleInterface');
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['role' => $user->getDefaultRole()]))
            ->will($this->returnValue(new Role($user->getDefaultRole())));

        $this->userManager->updateUser($user);

        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($encodedPassword, $user->getPassword());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Expected Symfony\Component\Security\Core\Role\RoleInterface, \stdClass given
     */
    public function testNotSupportedRole()
    {
        $user = $this->getUser();

        $this->metadata->expects($this->once())->method('getAssociationTargetClass')->willReturn('\stdClass');
        $this->om->expects($this->never())
            ->method('persist')
            ->with($this->equalTo($user));
        $this->om->expects($this->never())
            ->method('flush');

        $this->userManager->updateUser($user);
    }
}
