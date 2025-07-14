<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\EventListener\AbstractDemoDataFixturesListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class DemoDataFixturesListenerTestCase extends TestCase
{
    use EntityTrait;

    protected const LISTENERS = [
        'test_listener_1',
        'test_listener_2',
    ];

    protected OptionalListenerManager&MockObject $listenerManager;
    protected MigrationDataFixturesEvent&MockObject $event;
    protected AbstractDemoDataFixturesListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);
        $this->event = $this->createMock(MigrationDataFixturesEvent::class);

        $this->listener = $this->getListener();
        $this->listener->disableListener(self::LISTENERS[0]);
        $this->listener->disableListener(self::LISTENERS[1]);
    }

    /**
     * @return AbstractDemoDataFixturesListener
     */
    abstract protected function getListener();

    public function testOnPreLoad(): void
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->listenerManager->expects($this->once())
            ->method('disableListeners')
            ->with(self::LISTENERS);

        $this->listener->onPreLoad($this->event);
    }

    public function testOnPreLoadWithNoDemoFixtures(): void
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->listenerManager->expects($this->never())
            ->method('disableListeners');

        $this->listener->onPreLoad($this->event);
    }

    public function testOnPostLoad(): void
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->listenerManager->expects($this->once())
            ->method('enableListeners')
            ->with(self::LISTENERS);

        $this->listener->onPostLoad($this->event);
    }

    public function testOnPostLoadWithNoDemoFixtures(): void
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->listenerManager->expects($this->never())
            ->method('enableListeners');

        $this->listener->onPostLoad($this->event);
    }
}
