<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class DefaultUserProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var DefaultUserProvider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->provider = new DefaultUserProvider($this->configManager, $this->doctrineHelper);
    }

    public function testGetDefaultUser()
    {
        $user = $this->getEntity(User::class, ['first_name' => 'first name']);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('alias.key')
            ->willReturn(null);

        /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn($user);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(User::class)
            ->willReturn($repository);

        $this->assertSame($user, $this->provider->getDefaultUser('alias', 'key'));
    }

    public function testGetDefaultUserById()
    {
        $user = $this->getEntity(User::class, ['id' => 1, 'first_name' => 'first name']);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('alias.key')
            ->willReturn(1);

        /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(User::class)
            ->willReturn($repository);

        $this->assertSame($user, $this->provider->getDefaultUser('alias', 'key'));
    }

    public function testGetDefaultUserByNotExistId()
    {
        $user = $this->getEntity(User::class, ['id' => 2, 'first_name' => 'first name']);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('alias.key')
            ->willReturn(1);

        /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn($user);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(User::class)
            ->willReturn($repository);

        $this->assertSame($user, $this->provider->getDefaultUser('alias', 'key'));
    }
}
