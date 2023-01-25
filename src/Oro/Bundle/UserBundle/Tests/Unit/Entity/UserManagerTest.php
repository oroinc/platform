<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Repository\UserApiRepository;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Security\UserLoaderInterface;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class UserManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $userLoader;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EncoderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $encoderFactory;

    /** @var EnumValueProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $enumValueProvider;

    /** var Processor|\PHPUnit\Framework\MockObject\MockObject */
    private $emailProcessor;

    /** @var UserManager */
    private $userManager;

    protected function setUp(): void
    {
        $this->userLoader = $this->createMock(UserLoaderInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->encoderFactory = $this->createMock(EncoderFactoryInterface::class);
        $this->enumValueProvider = $this->createMock(EnumValueProvider::class);
        $this->emailProcessor = $this->createMock(Processor::class);

        $this->userLoader->expects(self::any())
            ->method('getUserClass')
            ->willReturn(User::class);

        $enumValueProvider = $this->createMock(EnumValueProvider::class);
        $enumValueProvider->expects(self::any())
            ->method('getEnumValueByCode')
            ->willReturnCallback(function ($code, $id) {
                return new TestEnumValue($id, $id);
            });

        $emailProcessorLink = $this->createMock(ServiceLink::class);
        $emailProcessorLink->expects(self::any())
            ->method('getService')
            ->willReturn($this->emailProcessor);

        $this->userManager = new UserManager(
            $this->userLoader,
            $this->doctrine,
            $this->encoderFactory,
            $this->enumValueProvider,
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

    private function expectGetPasswordEncoder(
        User $user
    ): PasswordEncoderInterface|\PHPUnit\Framework\MockObject\MockObject {
        $encoder = $this->createMock(PasswordEncoderInterface::class);
        $this->encoderFactory->expects(self::once())
            ->method('getEncoder')
            ->with($user)
            ->willReturn($encoder);

        return $encoder;
    }

    public function testGetApi(): void
    {
        $user = new User();
        $organization = new Organization();
        $userApi = new UserApi();

        $em = $this->expectGetEntityManager();
        $repository = $this->createMock(UserApiRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(UserApi::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('getApi')
            ->with($user, $organization)
            ->willReturn($userApi);

        self::assertSame($userApi, $this->userManager->getApi($user, $organization));
    }

    public function testGetApiWhenApiKeyDoesNotExist(): void
    {
        $user = new User();
        $organization = new Organization();

        $em = $this->expectGetEntityManager();
        $repository = $this->createMock(UserApiRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(UserApi::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('getApi')
            ->with($user, $organization)
            ->willReturn(null);

        self::assertNull($this->userManager->getApi($user, $organization));
    }

    public function testUpdateUserWithPlainPassword(): void
    {
        $password = 'password';
        $encodedPassword = 'encodedPassword';
        $salt = 'salt';

        $user = new User();
        $user->setPlainPassword($password);
        $user->setSalt($salt);

        $this->enumValueProvider->expects(self::once())
            ->method('getDefaultEnumValueByCode')
            ->with('auth_status')
            ->willReturn(null);

        $encoder = $this->expectGetPasswordEncoder($user);
        $encoder->expects(self::once())
            ->method('encodePassword')
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

        $this->enumValueProvider->expects(self::once())
            ->method('getDefaultEnumValueByCode')
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
        $defaultAuthStatus = new TestEnumValue('auth_status_1', 'Auth Status 1');

        $this->enumValueProvider->expects(self::once())
            ->method('getDefaultEnumValueByCode')
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
        $authStatus = new TestEnumValue('auth_status_1', 'Auth Status 1');
        $user->setAuthStatus($authStatus);

        $this->enumValueProvider->expects(self::never())
            ->method('getDefaultEnumValueByCode');

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

        $this->enumValueProvider->expects(self::once())
            ->method('getEnumValueByCode')
            ->willReturnCallback(function ($code, $id) {
                return new TestEnumValue($id, $id);
            });

        $this->userManager->setAuthStatus($user, UserManager::STATUS_RESET);
        self::assertEquals(UserManager::STATUS_RESET, $user->getAuthStatus()->getId());
    }
}
