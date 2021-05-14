<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutorInterface;
use Oro\Bundle\PlatformBundle\EventListener\AbstractDataFixturesListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

class AbstractDataFixturesListenerTest extends \PHPUnit\Framework\TestCase
{
    private const LISTENERS = ['test_listener_1', 'test_listener_2'];

    /** @var OptionalListenerManager|\PHPUnit\Framework\MockObject\MockObject */
    private $listenerManager;

    /** @var AbstractDataFixturesListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);

        $this->listener = new AbstractDataFixturesListener($this->listenerManager);
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

    public function testOnPreLoad()
    {
        $this->listenerManager->expects(self::once())
            ->method('disableListeners')
            ->with(self::LISTENERS);

        $this->listener->onPreLoad($this->getEvent(DataFixturesExecutorInterface::MAIN_FIXTURES));
    }

    public function testOnPostLoad()
    {
        $this->listenerManager->expects(self::once())
            ->method('enableListeners')
            ->with(self::LISTENERS);

        $this->listener->onPostLoad($this->getEvent(DataFixturesExecutorInterface::MAIN_FIXTURES));
    }
}
