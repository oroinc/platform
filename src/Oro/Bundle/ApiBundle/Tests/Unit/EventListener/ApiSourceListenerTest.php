<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\EventListener\ApiSourceListener;
use Oro\Bundle\ApiBundle\Provider\CacheManager;

class ApiSourceListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CacheManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheManager;

    /**
     * @var ApiSourceListener
     */
    private $listener;

    protected function setUp()
    {
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->listener = new ApiSourceListener($this->cacheManager);
    }

    public function testClearCache()
    {
        $this->cacheManager->expects($this->once())
            ->method('clearCaches');
        $this->cacheManager->expects($this->once())
            ->method('clearApiDocCache');
        $this->listener->clearCache();
    }
}
