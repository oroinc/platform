<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataAuditBundle\EventListener\DemoDataFixturesListener;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

class DemoDataFixturesListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $listenerManager;

    /** @var DemoDataFixturesListener */
    protected $listener;

    protected function setUp()
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);

        $this->listener = new DemoDataFixturesListener($this->listenerManager);
    }

    public function testOnPreLoadForNotDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(false);
        $this->listenerManager->expects(self::never())
            ->method('disableListener');

        $this->listener->onPreLoad($event);
    }

    public function testOnPreLoadForDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(true);
        $this->listenerManager->expects(self::once())
            ->method('disableListener')
            ->with(DemoDataFixturesListener::DATA_COLLECTOR_LISTENER);

        $this->listener->onPreLoad($event);
    }

    public function testOnPostLoadForNotDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(false);
        $this->listenerManager->expects(self::never())
            ->method('enableListener');

        $this->listener->onPostLoad($event);
    }

    public function testOnPostLoadForDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(true);
        $this->listenerManager->expects(self::once())
            ->method('enableListener')
            ->with(DemoDataFixturesListener::DATA_COLLECTOR_LISTENER);

        $this->listener->onPostLoad($event);
    }
}
