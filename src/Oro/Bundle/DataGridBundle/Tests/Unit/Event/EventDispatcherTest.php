<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Event;

use Oro\Bundle\DataGridBundle\Tests\Unit\Stub\GridEvent;

use Oro\Bundle\DataGridBundle\Event\EventDispatcher;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    const TEST_EVENT_NAME = 'test.event';

    /** @var  EventDispatcherInterface */
    protected $dispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $realDispatcherMock;

    public function setUp()
    {
        $this->realDispatcherMock = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->dispatcher         = new EventDispatcher($this->realDispatcherMock);
    }

    public function tearDown()
    {
        unset($this->realDispatcherMock, $this->dispatcher);
    }

    /**
     * @dataProvider eventDataProvider
     *
     * @param array $config
     * @param array $expectedEvents
     */
    public function testDispatch(array $config, array $expectedEvents)
    {
        $config   = DatagridConfiguration::create($config);
        $gridMock = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $gridMock->expects($this->any())->method('getConfig')->will($this->returnValue($config));

        foreach ($expectedEvents as $k => $event) {
            $this->realDispatcherMock->expects($this->at($k))->method('dispatch')
                ->with($event);
        }

        $event = new GridEvent($gridMock);
        $this->dispatcher->dispatch(self::TEST_EVENT_NAME, $event);
    }

    /**
     * @return array
     */
    public function eventDataProvider()
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
}
