<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\WorkflowBundle\EventListener\EventTriggerDemoDataFixturesListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventTriggerDemoDataFixturesListenerTest extends TestCase
{
    private OptionalListenerManager&MockObject $listenerManager;
    private EventTriggerDemoDataFixturesListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);

        $this->listener = new EventTriggerDemoDataFixturesListener($this->listenerManager);
    }

    public function testOnPreLoadForNotDemoFixtures(): void
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(false);
        $this->listenerManager->expects(self::never())
            ->method('disableListener');

        $this->listener->onPreLoad($event);
    }

    public function testOnPreLoadForDemoFixtures(): void
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(true);
        $this->listenerManager->expects(self::once())
            ->method('disableListener')
            ->with(EventTriggerDemoDataFixturesListener::EVENT_TRIGGER_COLLECTOR_LISTENER);

        $this->listener->onPreLoad($event);
    }

    public function testOnPostLoadForNotDemoFixtures(): void
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(false);
        $this->listenerManager->expects(self::never())
            ->method('enableListener');

        $this->listener->onPostLoad($event);
    }

    public function testOnPostLoadForDemoFixtures(): void
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(true);
        $this->listenerManager->expects(self::once())
            ->method('enableListener')
            ->with(EventTriggerDemoDataFixturesListener::EVENT_TRIGGER_COLLECTOR_LISTENER);

        $this->listener->onPostLoad($event);
    }
}
