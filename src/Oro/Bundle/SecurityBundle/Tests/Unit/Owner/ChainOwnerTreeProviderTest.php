<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use Oro\Bundle\SecurityBundle\Owner\ChainOwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ChainOwnerTreeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainOwnerTreeProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new ChainOwnerTreeProvider();
    }

    public function testSupports()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())->method('supports')->willReturn(true);

        $notSupported = $this->getProviderMock();
        $notSupported->expects($this->never())->method('supports');

        $this->provider->addProvider($provider);
        $this->provider->addProvider($notSupported);

        $this->assertTrue($this->provider->supports());
    }

    public function testAddProvider()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())->method('supports')->willReturn(true);

        // guard
        $this->assertFalse($this->provider->supports());

        $this->provider->addProvider($provider);
        $this->assertTrue($this->provider->supports());
    }

    public function testNotAddSameProvider()
    {
        $provider = $this->getOwnerTreeProviderMock();
        $defaultProvider = $this->getOwnerTreeProviderMock();

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($defaultProvider);
        $this->provider->addProvider($defaultProvider);

        self::assertAttributeCount(
            1,
            'providers',
            $this->provider
        );
    }

    public function testSupportsDefault()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->never())->method('supports');

        $default = $this->getProviderMock();

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->assertTrue($this->provider->supports());
    }

    public function testGetTree()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())->method('supports')->willReturn(true);
        $provider->expects($this->once())->method('getTree')->willReturn(new OwnerTree());

        $default = $this->getProviderMock();

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->assertInstanceOf(OwnerTreeInterface::class, $this->provider->getTree());
    }

    public function testGetTreeDefault()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())->method('supports')->willReturn(false);
        $provider->expects($this->never())->method('getTree');

        $default = $this->getProviderMock();
        $default->expects($this->once())->method('getTree')->willReturn(new OwnerTree());

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->assertInstanceOf(OwnerTreeInterface::class, $this->provider->getTree());
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\UnsupportedOwnerTreeProviderException
     * @expectedExceptionMessage Supported provider not found in chain
     */
    public function testGetTreeFailed()
    {
        $this->provider->getTree();
    }

    public function testClearCache()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())->method('clearCache');

        $default = $this->getProviderMock();
        $default->expects($this->once())->method('clearCache');

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->provider->clearCache();
    }

    public function testWarmUpCache()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())->method('warmUpCache');

        $default = $this->getProviderMock();
        $default->expects($this->once())->method('warmUpCache');

        $this->provider->addProvider($provider);
        $this->provider->setDefaultProvider($default);

        $this->provider->warmUpCache();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|OwnerTreeProviderInterface
     */
    protected function getProviderMock()
    {
        return $this->createMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');
    }

    protected function getOwnerTreeProviderMock()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $databaseChecker = $this->createMock(DatabaseChecker::class);
        $cache = $this->createMock(CacheProvider::class);
        $ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $treeProvider = new OwnerTreeProvider(
            $doctrine,
            $databaseChecker,
            $cache,
            $ownershipMetadataProvider,
            $tokenStorage
        );
        $treeProvider->setLogger($logger);

        return $treeProvider;
    }
}
