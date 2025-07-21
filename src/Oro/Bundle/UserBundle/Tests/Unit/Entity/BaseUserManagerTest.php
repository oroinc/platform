<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\Security\UserLoaderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BaseUserManagerTest extends TestCase
{
    private UserLoaderInterface&MockObject $userLoader;
    private ManagerRegistry&MockObject $doctrine;
    private PasswordHasherFactoryInterface&MockObject $passwordHasherFactory;
    private BaseUserManager $userManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->userLoader = $this->createMock(UserLoaderInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);

        $this->userLoader->expects(self::any())
            ->method('getUserClass')
            ->willReturn(User::class);

        $this->userManager = new BaseUserManager(
            $this->userLoader,
            $this->doctrine,
            $this->passwordHasherFactory
        );
    }

    private function expectGetEntityManager(): EntityManagerInterface&MockObject
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::atLeastOnce())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($em);

        return $em;
    }

    private function expectGetRepository(EntityManagerInterface&MockObject $em): EntityRepository&MockObject
    {
        $repository = $this->createMock(EntityRepository::class);
        $em->expects(self::atLeastOnce())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        return $repository;
    }

    private function expectGetPasswordHasher(UserInterface $user): PasswordHasherInterface&MockObject
    {
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->passwordHasherFactory->expects(self::once())
            ->method('getPasswordHasher')
            ->with($user)
            ->willReturn($passwordHasher);

        return $passwordHasher;
    }

    public function findUserDataProvider(): array
    {
        return [
            [$this->createMock(User::class)],
            [null]
        ];
    }

    public function testCreateUser(): void
    {
        self::assertInstanceOf(User::class, $this->userManager->createUser());
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
    }

    public function testUpdateUserWithoutPlainPassword(): void
    {
        $user = new User();

        $em = $this->expectGetEntityManager();
        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($user));
        $em->expects(self::once())
            ->method('flush');

        $this->userManager->updateUser($user);

        self::assertNull($user->getPlainPassword());
        self::assertNull($user->getPassword());
    }

    public function testGeneratePasswordWithDefaultLength(): void
    {
        $password = $this->userManager->generatePassword();
        self::assertNotEmpty($password);
        self::assertMatchesRegularExpression('/[\w\-]+/', $password);
        self::assertLessThanOrEqual(30, strlen($password));

        self::assertNotEquals($password, $this->userManager->generatePassword());
    }

    public function testGeneratePasswordWithCustomLength(): void
    {
        $maxLength = 10;
        $password = $this->userManager->generatePassword($maxLength);
        self::assertNotEmpty($password);
        self::assertMatchesRegularExpression('/[\w\-]+/', $password);
        self::assertLessThanOrEqual($maxLength, strlen($password));

        self::assertNotEquals($password, $this->userManager->generatePassword($maxLength));
    }

    public function testDeleteUser(): void
    {
        $user = $this->createMock(User::class);

        $em = $this->expectGetEntityManager();
        $em->expects(self::once())
            ->method('remove')
            ->with(self::identicalTo($user));
        $em->expects(self::once())
            ->method('flush');

        $this->userManager->deleteUser($user);
    }

    /**
     * @dataProvider findUserDataProvider
     */
    public function testFindUserBy($user): void
    {
        $criteria = ['id' => 1];

        $em = $this->expectGetEntityManager();
        $repository = $this->expectGetRepository($em);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn($user);

        self::assertSame($user, $this->userManager->findUserBy($criteria));
    }

    /**
     * @dataProvider findUserDataProvider
     */
    public function testFindUserByEmail($user): void
    {
        $email = 'test@example.com';

        $this->userLoader->expects(self::once())
            ->method('loadUserByEmail')
            ->with($email)
            ->willReturn($user);

        self::assertSame($user, $this->userManager->findUserByEmail($email));
    }

    /**
     * @dataProvider findUserDataProvider
     */
    public function testFindUserByUsername($user): void
    {
        $username = 'test';

        $this->userLoader->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with($username)
            ->willReturn($user);

        self::assertSame($user, $this->userManager->findUserByUsername($username));
    }

    /**
     * @dataProvider findUserDataProvider
     */
    public function testFindUserByUsernameOrEmail($user): void
    {
        $usernameOrEmail = 'test@test.com';

        $this->userLoader->expects(self::once())
            ->method('loadUser')
            ->with($usernameOrEmail)
            ->willReturn($user);

        self::assertSame($user, $this->userManager->findUserByUsernameOrEmail($usernameOrEmail));
    }

    /**
     * @dataProvider findUserDataProvider
     */
    public function testFindUserByConfirmationToken($user): void
    {
        $confirmationToken = 'test';

        $em = $this->expectGetEntityManager();
        $repository = $this->expectGetRepository($em);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['confirmationToken' => $confirmationToken])
            ->willReturn($user);

        self::assertSame($user, $this->userManager->findUserByConfirmationToken($confirmationToken));
    }

    public function testReloadUser(): void
    {
        $user = $this->createMock(User::class);

        $em = $this->expectGetEntityManager();
        $em->expects(self::once())
            ->method('refresh')
            ->with(self::identicalTo($user));

        $this->userManager->reloadUser($user);
    }
}
