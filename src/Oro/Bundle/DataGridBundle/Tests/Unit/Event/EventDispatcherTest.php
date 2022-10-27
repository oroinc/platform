<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Event;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\EventDispatcher;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use Oro\Bundle\DataGridBundle\Tests\Unit\Stub\GridConfigEvent;
use Oro\Bundle\DataGridBundle\Tests\Unit\Stub\GridEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

class EventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_EVENT_NAME = 'test.event';

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $realDispatcher;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->realDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->dispatcher = new EventDispatcher($this->realDispatcher);
    }

    /**
     * @dataProvider eventDataProvider
     */
    public function testDispatchGridEvent(array $config, array $expectedEvents)
    {
        $config = DatagridConfiguration::create($config);
        $grid = $this->createMock(DatagridInterface::class);
        $grid->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        $event = new GridEvent($grid);

        $events = [];
        foreach ($expectedEvents as $eventName) {
            $events[] = [$event, $eventName];
        }
        $this->realDispatcher->expects($this->exactly(count($events)))
            ->method('dispatch')
            ->withConsecutive(...$events);

        $this->dispatcher->dispatch($event, self::TEST_EVENT_NAME);
    }

    public function eventDataProvider(): array
    {
        return [
            'should raise at least 2 events'          => [
                ['name' => 'testGrid'],
                [self::TEST_EVENT_NAME, self::TEST_EVENT_NAME . '.' . 'testGrid']
            ],
            'should raise 3 events start with parent' => [
                ['name' => 'testGrid', SystemAwareResolver::KEY_EXTENDED_FROM => ['parent1']],
                [
                    self::TEST_EVENT_NAME,
                    self::TEST_EVENT_NAME . '.' . 'parent1',
                    self::TEST_EVENT_NAME . '.' . 'testGrid'
                ]
            ]
        ];
    }

    /**
     * @dataProvider eventDataProvider
     */
    public function testDispatchGridConfigEvent(array $config, array $expectedEvents)
    {
        $config = DatagridConfiguration::create($config);

        $event = new GridConfigEvent($config);

        $events = [];
        foreach ($expectedEvents as $eventName) {
            $events[] = [$event, $eventName];
        }
        $this->realDispatcher->expects($this->exactly(count($events)))
            ->method('dispatch')
            ->withConsecutive(...$events);

        $this->dispatcher->dispatch($event, self::TEST_EVENT_NAME);
    }

    public function testDispatchException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unexpected event type. Expected instance of GridEventInterface or GridConfigurationEventInterface'
        );
        $event = $this->createMock(Event::class);
        $this->dispatcher->dispatch($event);
    }
}
