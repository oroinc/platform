<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\EventListener\AbstractDemoDataFixturesListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Component\Testing\Unit\EntityTrait;

abstract class DemoDataFixturesListenerTestCase extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    protected const LISTENERS = [
        'test_listener_1',
        'test_listener_2',
    ];

    /** @var OptionalListenerManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $listenerManager;

    /** @var MigrationDataFixturesEvent|\PHPUnit\Framework\MockObject\MockObject */
    protected $event;

    /** @var AbstractDemoDataFixturesListener */
    protected $listener;

    /**
     * {@inheritDoc}
     */
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

    public function testOnPreLoad()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->listenerManager->expects($this->once())
            ->method('disableListeners')
            ->with(self::LISTENERS);

        $this->listener->onPreLoad($this->event);
    }

    public function testOnPreLoadWithNoDemoFixtures()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->listenerManager->expects($this->never())
            ->method('disableListeners');

        $this->listener->onPreLoad($this->event);
    }

    public function testOnPostLoad()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->listenerManager->expects($this->once())
            ->method('enableListeners')
            ->with(self::LISTENERS);

        $this->listener->onPostLoad($this->event);
    }

    public function testOnPostLoadWithNoDemoFixtures()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->listenerManager->expects($this->never())
            ->method('enableListeners');

        $this->listener->onPostLoad($this->event);
    }
}
