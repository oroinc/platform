<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\EventListener\ReportCacheCleanerListener;
use Oro\Component\Testing\Unit\EntityTrait;

class ReportCacheCleanerListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const PREFIX_CACHE_KEY = 'test_cache_key';

    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var ReportCacheCleanerListener */
    private $listener;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(Cache::class);

        $this->listener = new ReportCacheCleanerListener($this->cache, self::PREFIX_CACHE_KEY);
    }

    private function getReport(): Report
    {
        return $this->getEntity(Report::class, ['id' => 1]);
    }

    public function testCacheDoesNotHaveKey()
    {
        $this->cache->expects(self::once())
            ->method('contains')
            ->willReturn(false);
        $this->cache->expects(self::never())
            ->method('delete');

        $this->listener->postUpdate($this->getReport(), $this->createMock(LifecycleEventArgs::class));
    }

    public function testPostUpdateSuccess()
    {
        $this->cache->expects(self::once())
            ->method('contains')
            ->willReturn(true);
        $this->cache->expects(self::once())
            ->method('delete')
            ->with(self::PREFIX_CACHE_KEY . '.oro_report_table_1');

        $this->listener->postUpdate($this->getReport(), $this->createMock(LifecycleEventArgs::class));
    }
}
