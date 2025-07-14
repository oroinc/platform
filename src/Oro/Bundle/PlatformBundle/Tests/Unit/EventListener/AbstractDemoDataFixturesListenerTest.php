<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutorInterface;
use Oro\Bundle\PlatformBundle\EventListener\AbstractDemoDataFixturesListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractDemoDataFixturesListenerTest extends TestCase
{
    private const LISTENERS = ['test_listener_1', 'test_listener_2'];

    private OptionalListenerManager&MockObject $listenerManager;
    private AbstractDemoDataFixturesListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);

        $this->listener = new AbstractDemoDataFixturesListener($this->listenerManager);
        $this->listener->disableListener(self::LISTENERS[0]);
        $this->listener->disableListener(self::LISTENERS[1]);
    }

    private function getEvent(string $fixturesType): MigrationDataFixturesEvent
    {
        return new MigrationDataFixturesEvent(
            $this->createMock(ObjectManager::class),
            $fixturesType
        );
    }

    public function testOnPreLoadForDemoFixtures(): void
    {
        $this->listenerManager->expects(self::once())
            ->method('disableListeners')
            ->with(self::LISTENERS);

        $this->listener->onPreLoad($this->getEvent(DataFixturesExecutorInterface::DEMO_FIXTURES));
    }

    public function testOnPostLoadForDemoFixtures(): void
    {
        $this->listenerManager->expects(self::once())
            ->method('enableListeners')
            ->with(self::LISTENERS);

        $this->listener->onPostLoad($this->getEvent(DataFixturesExecutorInterface::DEMO_FIXTURES));
    }

    public function testOnPreLoadForNotDemoFixtures(): void
    {
        $this->listenerManager->expects(self::never())
            ->method(self::anything());

        $this->listener->onPreLoad($this->getEvent(DataFixturesExecutorInterface::MAIN_FIXTURES));
    }

    public function testOnPostLoadForNotDemoFixtures(): void
    {
        $this->listenerManager->expects(self::never())
            ->method(self::anything());

        $this->listener->onPostLoad($this->getEvent(DataFixturesExecutorInterface::MAIN_FIXTURES));
    }
}
