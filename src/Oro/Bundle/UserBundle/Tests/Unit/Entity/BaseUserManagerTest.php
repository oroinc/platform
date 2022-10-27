<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\Security\UserLoaderInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BaseUserManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $userLoader;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EncoderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $encoderFactory;

    /** @var BaseUserManager */
    private $userManager;

    protected function setUp(): void
    {
        $this->userLoader = $this->createMock(UserLoaderInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->encoderFactory = $this->createMock(EncoderFactoryInterface::class);

        $this->userLoader->expects(self::any())
            ->method('getUserClass')
            ->willReturn(User::class);

        $this->userManager = new BaseUserManager(
            $this->userLoader,
            $this->doctrine,
            $this->encoderFactory
        );
    }

    /**
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function expectGetEntityManager()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::atLeastOnce())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($em);

        return $em;
    }

    /**
     * @param EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em
     *
     * @return EntityRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function expectGetRepository($em)
    {
        $repository = $this->createMock(EntityRepository::class);
        $em->expects(self::atLeastOnce())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        return $repository;
    }

    /**
     * @param UserInterface $user
     *
     * @return PasswordEncoderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function expectGetPasswordEncoder(UserInterface $user)
    {
        $encoder = $this->createMock(PasswordEncoderInterface::class);
        $this->encoderFactory->expects(self::once())
            ->method('getEncoder')
            ->with($user)
            ->willReturn($encoder);

        return $encoder;
    }

    public function findUserDataProvider(): array
    {
        return [
            [$this->createMock(User::class)],
            [null]
        ];
    }

    public function testCreateUser()
    {
        self::assertInstanceOf(User::class, $this->userManager->createUser());
    }

    public function testUpdateUserWithPlainPassword()
    {
        $password = 'password';
        $encodedPassword = 'encodedPassword';
        $salt = 'salt';

        $user = new User();
        $user->setPlainPassword($password);
        $user->setSalt($salt);

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
    }

    public function testUpdateUserWithoutPlainPassword()
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

    public function testGeneratePasswordWithDefaultLength()
    {
        $password = $this->userManager->generatePassword();
        self::assertNotEmpty($password);
        self::assertMatchesRegularExpression('/[\w\-]+/', $password);
        self::assertLessThanOrEqual(30, strlen($password));

        self::assertNotEquals($password, $this->userManager->generatePassword());
    }

    public function testGeneratePasswordWithCustomLength()
    {
        $maxLength = 10;
        $password = $this->userManager->generatePassword($maxLength);
        self::assertNotEmpty($password);
        self::assertMatchesRegularExpression('/[\w\-]+/', $password);
        self::assertLessThanOrEqual($maxLength, strlen($password));

        self::assertNotEquals($password, $this->userManager->generatePassword($maxLength));
    }

    public function testDeleteUser()
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
    public function testFindUserBy($user)
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
    public function testFindUserByEmail($user)
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
    public function testFindUserByUsername($user)
    {
        $username = 'test';

        $this->userLoader->expects(self::once())
            ->method('loadUserByUsername')
            ->with($username)
            ->willReturn($user);

        self::assertSame($user, $this->userManager->findUserByUsername($username));
    }

    /**
     * @dataProvider findUserDataProvider
     */
    public function testFindUserByUsernameOrEmail($user)
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
    public function testFindUserByConfirmationToken($user)
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

    public function testReloadUser()
    {
        $user = $this->createMock(User::class);

        $em = $this->expectGetEntityManager();
        $em->expects(self::once())
            ->method('refresh')
            ->with(self::identicalTo($user));

        $this->userManager->reloadUser($user);
    }
}
