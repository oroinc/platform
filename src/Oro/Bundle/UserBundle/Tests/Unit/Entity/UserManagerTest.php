<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Security\UserLoaderInterface;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class UserManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $userLoader;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var PasswordHasherFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $passwordHasherFactory;

    /** @var EnumOptionsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $enumOptionsProvider;

    /** var Processor|\PHPUnit\Framework\MockObject\MockObject */
    private $emailProcessor;

    /** @var UserManager */
    private $userManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->userLoader = $this->createMock(UserLoaderInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->enumOptionsProvider = $this->createMock(EnumOptionsProvider::class);
        $this->emailProcessor = $this->createMock(Processor::class);

        $this->userLoader->expects(self::any())
            ->method('getUserClass')
            ->willReturn(User::class);

        $enumOptionsProvider = $this->createMock(EnumOptionsProvider::class);
        $enumOptionsProvider->expects(self::any())
            ->method('getEnumOptionByCode')
            ->willReturnCallback(function ($code, $id) {
                return new TestEnumValue($code, 'Test', $id);
            });

        $emailProcessorLink = $this->createMock(ServiceLink::class);
        $emailProcessorLink->expects(self::any())
            ->method('getService')
            ->willReturn($this->emailProcessor);

        $this->userManager = new UserManager(
            $this->userLoader,
            $this->doctrine,
            $this->passwordHasherFactory,
            $this->enumOptionsProvider,
            $emailProcessorLink
        );
    }

    private function expectGetEntityManager(): EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::atLeastOnce())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($em);

        return $em;
    }

    private function expectGetPasswordHasher(
        User $user
    ): PasswordHasherInterface|\PHPUnit\Framework\MockObject\MockObject {
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->passwordHasherFactory->expects(self::once())
            ->method('getPasswordHasher')
            ->with($user)
            ->willReturn($passwordHasher);

        return $passwordHasher;
    }

    public function testUpdateUserWithPlainPassword(): void
    {
        $password = 'password';
        $encodedPassword = 'encodedPassword';
        $salt = 'salt';

        $user = new User();
        $user->setUserIdentifier('test');
        $user->setPlainPassword($password);
        $user->setSalt($salt);

        $this->enumOptionsProvider->expects(self::once())
            ->method('getDefaultEnumOptionByCode')
            ->with('auth_status')
            ->willReturn(null);

        $passwordHasher = $this->expectGetPasswordHasher($user);
        $passwordHasher->expects(self::once())
            ->method('hash')
            ->with($password, $salt)
            ->willReturn($encodedPassword);

        $em = $this->expectGetEntityManager();
        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($user));
        $em->expects(self::once())
            ->method('flush');

        $this->userManager->updateUser($user);

        self::assertNull($user->getPlainPassword());
        self::assertEquals($encodedPassword, $user->getPassword());
        self::assertNull($user->getAuthStatus());
    }

    public function testUpdateUserWithoutPlainPassword(): void
    {
        $user = new User();

        $this->enumOptionsProvider->expects(self::once())
            ->method('getDefaultEnumOptionByCode')
            ->with('auth_status')
            ->willReturn(null);

        $em = $this->expectGetEntityManager();
        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($user));
        $em->expects(self::once())
            ->method('flush');

        $this->userManager->updateUser($user);

        self::assertNull($user->getPlainPassword());
        self::assertNull($user->getPassword());
        self::assertNull($user->getAuthStatus());
    }

    public function testUpdateUserForUserWithoutAuthStatus(): void
    {
        $user = new User();
        $defaultAuthStatus = new TestEnumValue(UserManager::AUTH_STATUS_ENUM_CODE, 'Auth Status 1', 'auth_status_1');

        $this->enumOptionsProvider->expects(self::once())
            ->method('getDefaultEnumOptionByCode')
            ->with('auth_status')
            ->willReturn($defaultAuthStatus);

        $em = $this->expectGetEntityManager();
        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($user));
        $em->expects(self::once())
            ->method('flush');

        $this->userManager->updateUser($user);

        self::assertSame($defaultAuthStatus, $user->getAuthStatus());
    }

    public function testUpdateUserForUserWithAuthStatus(): void
    {
        $user = new User();
        $authStatus = new TestEnumValue(UserManager::AUTH_STATUS_ENUM_CODE, 'Auth Status 1', 'auth_status_1');
        $user->setAuthStatus($authStatus);

        $this->enumOptionsProvider->expects(self::never())
            ->method('getDefaultEnumOptionByCode');

        $em = $this->expectGetEntityManager();
        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($user));
        $em->expects(self::once())
            ->method('flush');

        $this->userManager->updateUser($user);

        self::assertSame($authStatus, $user->getAuthStatus());
    }

    public function testSetAuthStatus(): void
    {
        $user = new User();
        self::assertNull($user->getAuthStatus());

        $this->enumOptionsProvider->expects(self::once())
            ->method('getEnumOptionByCode')
            ->willReturnCallback(function ($code, $id) {
                return new TestEnumValue(UserManager::AUTH_STATUS_ENUM_CODE, 'Test', $id);
            });

        $this->userManager->setAuthStatus($user, UserManager::STATUS_RESET);
        self::assertEquals(UserManager::STATUS_RESET, $user->getAuthStatus()->getInternalId());
    }
}
