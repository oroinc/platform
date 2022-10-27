<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Manager;

use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Bundle\CacheBundle\Provider\SyncCacheInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class OroDataCacheManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testSync()
    {
        $syncProvider = $this->createMock(SyncCacheInterface::class);
        $notSyncProvider = $this->createMock(SyncCacheInterface::class);

        $syncProvider->expects($this->once())
            ->method('sync');

        $manager = new OroDataCacheManager();
        $manager->registerCacheProvider($syncProvider);
        $manager->registerCacheProvider($notSyncProvider);

        $manager->sync();
    }

    public function testClear()
    {
        $clearableProvider = $this->createMock(AdapterInterface::class);
        $clearableProvider->expects($this->once())
            ->method('clear');

        $notClearableProvider = $this->createMock(SyncCacheInterface::class);
        $notClearableProvider->expects($this->never())
            ->method($this->anything());

        $manager = new OroDataCacheManager();
        $manager->registerCacheProvider($clearableProvider);
        $manager->registerCacheProvider($notClearableProvider);
        $manager->clear();
    }
}
