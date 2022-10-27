<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\UserLoaderInterface;
use Oro\Bundle\UserBundle\Security\UserProvider;
use Oro\Bundle\UserBundle\Tests\Unit\Fixture\RegularUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProviderTest extends \PHPUnit\Framework\TestCase
{
    private const USER_CLASS = User::class;

    /** @var UserLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $userLoader;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var UserProvider */
    private $userProvider;

    protected function setUp(): void
    {
        $this->userLoader = $this->createMock(UserLoaderInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->userLoader->expects(self::any())
            ->method('getUserClass')
            ->willReturn(self::USER_CLASS);

        $this->userProvider = new UserProvider($this->userLoader, $this->doctrine);
    }

    public function testLoadUserForExistingUsername()
    {
        $username = 'foobar';
        $user = $this->createMock(self::USER_CLASS);

        $this->userLoader->expects(self::once())
            ->method('loadUser')
            ->with($username)
            ->willReturn($user);

        self::assertSame(
            $user,
            $this->userProvider->loadUserByUsername($username)
        );
    }

    public function testLoadUserForNotExistingUsername()
    {
        $this->expectException(UsernameNotFoundException::class);
        $username = 'foobar';
        $this->userLoader->expects(self::once())
            ->method('loadUser')
            ->with($username)
            ->willReturn(null);

        $this->userProvider->loadUserByUsername($username);
    }

    public function testRefreshUserNotFound()
    {
        $this->expectException(UsernameNotFoundException::class);
        $user = $this->createMock(self::USER_CLASS);
        $user->expects(self::any())
            ->method('getId')
            ->willReturn(123);

        $manager = $this->createMock(ObjectManager::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::USER_CLASS)
            ->willReturn($manager);
        $manager->expects(self::once())
            ->method('refresh')
            ->with($user)
            ->willThrowException(new ORMInvalidArgumentException('Not managed'));
        $manager->expects(self::once())
            ->method('find')
            ->with(self::USER_CLASS, $user->getId())
            ->willReturn(null);

        $this->userProvider->refreshUser($user);
    }

    public function testRefreshUserManaged()
    {
        $user = $this->createMock(self::USER_CLASS);
        $user->expects(self::any())
            ->method('getId')
            ->willReturn(123);

        $manager = $this->createMock(ObjectManager::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::USER_CLASS)
            ->willReturn($manager);
        $manager->expects(self::once())
            ->method('refresh')
            ->with(self::identicalTo($user));
        $manager->expects(self::never())
            ->method('find');

        $this->userProvider->refreshUser($user);
    }

    public function testRefreshManagedUser()
    {
        $user = $this->createMock(self::USER_CLASS);
        $user->expects(self::any())
            ->method('getId')
            ->willReturn(123);

        $manager = $this->createMock(ObjectManager::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::USER_CLASS)
            ->willReturn($manager);
        $manager->expects(self::once())
            ->method('refresh')
            ->with(self::identicalTo($user))
            ->willThrowException(new ORMInvalidArgumentException('Not managed'));
        $manager->expects(self::once())
            ->method('find')
            ->with(self::USER_CLASS, $user->getId())
            ->willReturn($user);

        $this->userProvider->refreshUser($user);
    }

    public function testRefreshUserNotOroUser()
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Expected an instance of Oro\Bundle\UserBundle\Entity\User, but got');

        $user = $this->createMock(RegularUser::class);
        $this->userProvider->refreshUser($user);
    }

    public function testSupportsClassForSupportedUserObject()
    {
        $this->assertTrue($this->userProvider->supportsClass(self::USER_CLASS));
    }

    public function testSupportsClassForNotSupportedUserObject()
    {
        $this->assertFalse($this->userProvider->supportsClass(RegularUser::class));
    }
}
