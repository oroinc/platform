<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\UserLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserLoaderTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private ConfigManager&MockObject $configManager;
    private UserLoader $userLoader;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->userLoader = new UserLoader($this->doctrine, $this->configManager);
    }

    private function expectGetRepository(): UserRepository&MockObject
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::atLeastOnce())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($em);

        $repository = $this->createMock(UserRepository::class);
        $em->expects(self::atLeastOnce())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        return $repository;
    }

    public function findUserDataProvider(): array
    {
        return [
            [$this->createMock(User::class)],
            [null]
        ];
    }

    public function testGetUserClass(): void
    {
        self::assertEquals(User::class, $this->userLoader->getUserClass());
    }

    /**
     * @dataProvider findUserDataProvider
     */
    public function testLoadUserByUsername(?User $user): void
    {
        $username = 'test';

        $repository = $this->expectGetRepository();
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['username' => $username])
            ->willReturn($user);

        self::assertSame($user, $this->userLoader->loadUserByIdentifier($username));
    }

    /**
     * @dataProvider findUserDataProvider
     */
    public function testLoadUserByEmail(?User $user): void
    {
        $email = 'test@example.com';
        $caseInsensitiveEmailAddressesEnabled = true;

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_user.case_insensitive_email_addresses_enabled')
            ->willReturn($caseInsensitiveEmailAddressesEnabled);

        $repository = $this->expectGetRepository();
        $repository->expects(self::once())
            ->method('findUserByEmail')
            ->with($email, $caseInsensitiveEmailAddressesEnabled)
            ->willReturn($user);

        self::assertSame($user, $this->userLoader->loadUserByEmail($email));
    }

    public function testLoadUserWhenUserFoundByUsername(): void
    {
        $login = 'test@example.com';
        $user = $this->createMock(User::class);

        $repository = $this->expectGetRepository();
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['username' => $login])
            ->willReturn($user);

        self::assertSame($user, $this->userLoader->loadUser($login));
    }

    public function testLoadUserWhenUserFoundByEmail(): void
    {
        $login = 'test@example.com';
        $caseInsensitiveEmailAddressesEnabled = false;
        $user = $this->createMock(User::class);

        $repository = $this->expectGetRepository();
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['username' => $login])
            ->willReturn(null);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_user.case_insensitive_email_addresses_enabled')
            ->willReturn($caseInsensitiveEmailAddressesEnabled);

        $repository->expects(self::once())
            ->method('findUserByEmail')
            ->with($login, $caseInsensitiveEmailAddressesEnabled)
            ->willReturn($user);

        self::assertSame($user, $this->userLoader->loadUser($login));
    }

    public function testLoadUserWhenUserNotFoundByUsernameAndLoginIsNotEmail(): void
    {
        $login = 'test';

        $repository = $this->expectGetRepository();
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['username' => $login])
            ->willReturn(null);

        self::assertNull($this->userLoader->loadUser($login));
    }

    public function testLoadUserWhenUserNotFoundByUsernameAndEmail(): void
    {
        $login = 'test@example.com';
        $caseInsensitiveEmailAddressesEnabled = false;

        $repository = $this->expectGetRepository();
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['username' => $login])
            ->willReturn(null);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_user.case_insensitive_email_addresses_enabled')
            ->willReturn($caseInsensitiveEmailAddressesEnabled);

        $repository->expects(self::once())
            ->method('findUserByEmail')
            ->with($login, $caseInsensitiveEmailAddressesEnabled)
            ->willReturn(null);

        self::assertNull($this->userLoader->loadUser($login));
    }
}
