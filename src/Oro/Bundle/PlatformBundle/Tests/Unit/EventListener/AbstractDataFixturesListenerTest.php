<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\EventListener\AbstractDataFixturesListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

class AbstractDataFixturesListenerTest extends \PHPUnit\Framework\TestCase
{
    const LISTENERS = ['test_listener_1', 'test_listener_2'];

    /** @var OptionalListenerManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $listenerManager;

    /** @var AbstractDataFixturesListener */
    protected $listener;

    protected function setUp()
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);

        $this->listener = new AbstractDataFixturesListener($this->listenerManager);
        $this->listener->disableListener(self::LISTENERS[0]);
        $this->listener->disableListener(self::LISTENERS[1]);
    }

    /**
     * @dataProvider methodsDataProvider
     *
     * @param bool $isDemoData
     */
    public function testMethods(bool $isDemoData)
    {
        /** @var MigrationDataFixturesEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(MigrationDataFixturesEvent::class);
        $event->expects($this->any())
            ->method('isDemoFixtures')
            ->willReturn($isDemoData);
        $event->expects($this->never())
            ->method('log');

        $this->assertListenerManagerCalled($isDemoData);

        $this->listener->onPreLoad($event);
        $this->listener->onPostLoad($event);
    }

    /**
     * @param bool $isDemoData
     */
    protected function assertListenerManagerCalled(bool $isDemoData)
    {
        $this->listenerManager->expects($this->at(0))
            ->method('disableListeners')
            ->with(self::LISTENERS);
        $this->listenerManager->expects($this->at(1))
            ->method('enableListeners')
            ->with(self::LISTENERS);
    }

    /**
     * @return array
     */
    public function methodsDataProvider()
    {
        return [
            ['isDemoData' => false],
            ['isDemoData' => true]
        ];
    }
}
