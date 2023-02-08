<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\EventListener\ReportCacheCleanerListener;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Contracts\Cache\CacheInterface;

class ReportCacheCleanerListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const PREFIX_CACHE_KEY = 'test_cache_key';

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var ReportCacheCleanerListener */
    private $listener;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);

        $this->listener = new ReportCacheCleanerListener($this->cache, self::PREFIX_CACHE_KEY);
    }

    private function getReport(): Report
    {
        return $this->getEntity(Report::class, ['id' => 1]);
    }

    public function testCacheDoesNotHaveKey()
    {
        $this->cache->expects(self::once())
            ->method('delete');

        $this->listener->postUpdate($this->getReport(), $this->createMock(LifecycleEventArgs::class));
    }

    public function testPostUpdateSuccess()
    {
        $this->cache->expects(self::once())
            ->method('delete')
            ->with(self::PREFIX_CACHE_KEY . '.oro_report_table_1');

        $this->listener->postUpdate($this->getReport(), $this->createMock(LifecycleEventArgs::class));
    }
}
