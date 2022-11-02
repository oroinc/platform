<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

class DefaultUserProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var DefaultUserProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new DefaultUserProvider($this->configManager, $this->doctrine);
    }

    private function getUser(int $id): User
    {
        $user = new User();
        $user->setId($id);

        return $user;
    }

    public function testGetDefaultUserWhenItIsNotSetInConfig(): void
    {
        $user = $this->getUser(1);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('alias.key')
            ->willReturn(null);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn($user);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        self::assertSame($user, $this->provider->getDefaultUser('alias.key'));
    }

    public function testGetDefaultUserWhenItIsSetInConfigAndExistsInDatabase(): void
    {
        $user = $this->getUser(1);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('alias.key')
            ->willReturn(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('find')
            ->with(User::class, 1)
            ->willReturn($user);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($em);

        self::assertSame($user, $this->provider->getDefaultUser('alias.key'));
    }

    public function testGetDefaultUserWhenItIsSetInConfigAndDoesNotExistInDatabase(): void
    {
        $user = $this->getUser(2);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('alias.key')
            ->willReturn(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('find')
            ->with(User::class, 1)
            ->willReturn(null);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn($user);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($em);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        self::assertSame($user, $this->provider->getDefaultUser('alias.key'));
    }


    public function testGetDefaultUserWhenItIsSetInConfigAndDoesNotExistInDatabaseAndAnotherDefaultUserNotFound(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('alias.key')
            ->willReturn(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('find')
            ->with(User::class, 1)
            ->willReturn(null);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn(null);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($em);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        self::assertNull($this->provider->getDefaultUser('alias.key'));
    }
}
