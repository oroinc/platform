<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\ReportBundle\EventListener\ReportCacheCleanerListener;
use Oro\Bundle\ReportBundle\Tests\Unit\Stub\ReportStub;

class ReportCacheCleanerListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \PHPUnit_Framework_MockObject_MockObject | LifecycleEventArgs */
    protected $lifecycleEventArgs;

    /** @var  \PHPUnit_Framework_MockObject_MockObject | Cache */
    protected $reportCacheManager;

    /** @var  ReportCacheCleanerListener */
    protected $reportListener;

    protected function setUp()
    {
        $this->lifecycleEventArgs = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()->getMock();

        $this->reportCacheManager = $this->getMockBuilder(Cache::class)
            ->disableOriginalConstructor()->getMock();

        $cachekey = 'comeKay';

        $this->reportListener = new ReportCacheCleanerListener($this->reportCacheManager, $cachekey);

        parent::setUp();
    }

    public function testWithNotSupportedEntity()
    {
        $this->reportCacheManager->expects(self::never())->method('delete');
        $this->reportCacheManager->expects(self::never())->method('contains');

        $this->reportListener->postUpdate($this->lifecycleEventArgs);
    }

    public function testCacheDoesNotHaveKey()
    {
        $this->lifecycleEventArgs->expects(self::once())->method('getEntity')
            ->willReturn($this->getReportEntity());
        $this->reportCacheManager->expects(self::once())->method('contains')->willReturn(false);
        $this->reportCacheManager->expects(self::never())->method('delete');

        $this->reportListener->postUpdate($this->lifecycleEventArgs);
    }

    public function testPostUpdateSuccess()
    {
        $this->lifecycleEventArgs->expects(self::once())->method('getEntity')
            ->willReturn($this->getReportEntity());
        $this->reportCacheManager->expects(self::once())->method('contains')->willReturn(true);
        $this->reportCacheManager->expects(self::once())->method('delete')
            ->with('comeKay.oro_report_table_1');

        $this->reportListener->postUpdate($this->lifecycleEventArgs);
    }

    /**
     * @return ReportStub
     */
    protected function getReportEntity()
    {
        $entity = new ReportStub();
        $entity->setId(1);

        return $entity;
    }
}
