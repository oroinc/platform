<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMInvalidArgumentException;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class BaseUserManagerTest extends \PHPUnit\Framework\TestCase
{
    private const USER_CLASS = 'Oro\Bundle\UserBundle\Entity\User';
    private const TEST_NAME  = 'Jack';
    private const TEST_EMAIL = 'jack@jackmail.net';

    /** @var User */
    protected $user;

    /** @var BaseUserManager */
    protected $userManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UserRepository */
    protected $repository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EncoderFactoryInterface */
    protected $ef;

    protected function setUp()
    {
        $this->ef = $this->createMock(EncoderFactoryInterface::class);
        $class = $this->createMock(ClassMetadata::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->repository = $this->createMock(UserRepository::class);

        $this->em->expects(self::any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->repository);

        $this->em->expects(self::any())
            ->method('getClassMetadata')
            ->with(self::USER_CLASS)
            ->willReturn($class);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $class->expects(self::any())
            ->method('getName')
            ->willReturn(self::USER_CLASS);

        $this->userManager = new BaseUserManager(self::USER_CLASS, $this->registry, $this->ef);
    }

    public function testGetClass()
    {
        self::assertEquals(self::USER_CLASS, $this->userManager->getClass());
    }

    public function testCreateUser()
    {
        self::assertInstanceOf(self::USER_CLASS, $this->getUser());
    }

    public function testDeleteUser()
    {
        $user = $this->getUser();

        $this->em->expects(self::once())
            ->method('remove')
            ->with(self::identicalTo($user));
        $this->em->expects(self::once())
            ->method('flush');

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

        $encoder = $this->createMock(PasswordEncoderInterface::class);
        $encoder->expects(self::once())
            ->method('encodePassword')
            ->with($user->getPlainPassword(), $user->getSalt())
            ->willReturn($encodedPassword);

        $this->ef->expects(self::once())
            ->method('getEncoder')
            ->with($user)
            ->willReturn($encoder);

        $this->em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($user));
        $this->em->expects(self::once())
            ->method('flush');

        $this->userManager->updateUser($user);

        self::assertEquals(self::TEST_EMAIL, $user->getEmail());
        self::assertEquals($encodedPassword, $user->getPassword());
    }

    public function testFindUserBy()
    {
        $user = $this->getUser();
        $criteria = ['id' => 1];

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn($user);

        self::assertSame($user, $this->userManager->findUserBy(['id' => 1]));
    }

    public function testFindUserByUsername()
    {
        $user = $this->getUser();
        $criteria = ['username' => self::TEST_NAME];

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn($user);

        self::assertSame($user, $this->userManager->findUserByUsername(self::TEST_NAME));
    }

    public function testFindUserByEmail()
    {
        $user = $this->getUser();

        $this->repository->expects(self::once())
            ->method('findUserByEmail')
            ->with(self::TEST_EMAIL, false)
            ->willReturn($user);

        self::assertSame($user, $this->userManager->findUserByEmail(self::TEST_EMAIL));
    }

    public function testFindUserByToken()
    {
        $user = $this->getUser();
        $criteria = ['confirmationToken' => self::TEST_NAME];

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn($user);

        self::assertSame($user, $this->userManager->findUserByConfirmationToken(self::TEST_NAME));
    }

    public function testReloadUser()
    {
        $user = $this->getUser();

        $this->em->expects(self::once())
            ->method('refresh')
            ->with(self::identicalTo($user));

        $this->userManager->reloadUser($user);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testRefreshUserNotFound()
    {
        $user = $this->createMock(User::class);
        $user->expects(self::any())
            ->method('getId')
            ->willReturn(42);

        $this->em->expects(self::once())
            ->method('refresh')
            ->with($user)
            ->willThrowException(new ORMInvalidArgumentException('Not managed'));

        $this->repository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn(null);

        $this->userManager->refreshUser($user);
    }

    public function testRefreshUserManaged()
    {
        $user = $this->createMock(User::class);
        $user->expects(self::any())
            ->method('getId')
            ->willReturn(42);

        $this->em->expects(self::once())
            ->method('refresh')
            ->with(self::identicalTo($user));

        $this->repository->expects(self::never())
            ->method('find');

        $this->userManager->refreshUser($user);
    }

    public function testRefreshManagedUser()
    {
        $user = $this->createMock(User::class);
        $user->expects(self::any())
            ->method('getId')
            ->willReturn(42);

        $this->em->expects(self::once())
            ->method('refresh')
            ->with(self::identicalTo($user))
            ->willThrowException(new ORMInvalidArgumentException('Not managed'));

        $this->repository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($user);

        $this->userManager->refreshUser($user);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Account is not supported
     */
    public function testRefreshUserNotSupported()
    {
        $user = $this->createMock(UserInterface::class);
        $this->userManager->refreshUser($user);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @expectedExceptionMessage Expected an instance of Oro\Bundle\UserBundle\Entity\UserInterface, but got
     */
    public function testRefreshUserNotOroUser()
    {
        $user = $this->createMock(UserInterface::class);
        $userManager = new BaseUserManager(UserInterface::class, $this->registry, $this->ef);

        $userManager->refreshUser($user);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUserByUsernameNotFound()
    {
        $criteria = ['username' => self::TEST_NAME];

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn(null);

        $this->userManager->loadUserByUsername(self::TEST_NAME);
    }

    public function testLoadUserByUsername()
    {
        $user = $this->getUser();
        $criteria = ['username' => self::TEST_NAME];

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn($user);

        self::assertSame($user, $this->userManager->loadUserByUsername(self::TEST_NAME));
    }

    public function testSupportsClass()
    {
        self::assertTrue($this->userManager->supportsClass(self::USER_CLASS));
        self::assertFalse($this->userManager->supportsClass('stdClass'));
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
            $role = new Role(User::ROLE_ADMINISTRATOR);
            $user->addRole($role);
        }

        return $user;
    }
}
