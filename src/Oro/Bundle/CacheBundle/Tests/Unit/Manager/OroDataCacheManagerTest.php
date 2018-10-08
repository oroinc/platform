<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Manager;

use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;

class OroDataCacheManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testSync()
    {
        $syncProvider    = $this->createMock('Oro\Bundle\CacheBundle\Provider\SyncCacheInterface');
        $notSyncProvider = $this->createMock('Oro\Bundle\CacheBundle\Provider\SyncCacheInterface');

        $syncProvider->expects($this->once())
            ->method('sync');

        $manager = new OroDataCacheManager();
        $manager->registerCacheProvider($syncProvider);
        $manager->registerCacheProvider($notSyncProvider);

        $manager->sync();
    }

    public function testClear()
    {
        $clearableProvider = $this->createMock('Doctrine\Common\Cache\ClearableCache');
        $clearableProvider->expects($this->once())
            ->method('deleteAll');

        $notClearableProvider = $this->createMock('Doctrine\Common\Cache\Cache');
        $notClearableProvider->expects($this->never())
            ->method($this->anything());

        $manager = new OroDataCacheManager();
        $manager->registerCacheProvider($clearableProvider);
        $manager->registerCacheProvider($notClearableProvider);
        $manager->clear();
    }
}
