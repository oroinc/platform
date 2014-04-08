<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Manager;

use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;

class OroDataCacheManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testSync()
    {
        $syncProvider    = $this->getMock('Oro\Bundle\CacheBundle\Provider\SyncCacheInterface');
        $notSyncProvider = $this->getMock('Oro\Bundle\CacheBundle\Provider\SyncCacheInterface');

        $syncProvider->expects($this->once())
            ->method('sync');

        $manager = new OroDataCacheManager();
        $manager->registerCacheProvider($syncProvider);
        $manager->registerCacheProvider($notSyncProvider);

        $manager->sync();
    }
}
