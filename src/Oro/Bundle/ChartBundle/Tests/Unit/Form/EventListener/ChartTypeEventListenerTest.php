<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ChartBundle\Form\EventListener\ChartTypeEventListener;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ChartTypeEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChartTypeEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new ChartTypeEventListener();
    }

    public function testGetSubscribedEvents()
    {
        $events = $this->listener->getSubscribedEvents();

        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
        $this->assertArrayHasKey(FormEvents::SUBMIT, $events);
    }

    /**
     * @dataProvider chartConfigsProvider
     */
    public function testPostSubmit(array $data, array $expected)
    {
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $event->expects($this->once())
            ->method('setData')
            ->with($expected);

        $this->listener->onSubmit($event);
    }

    /**
     * @dataProvider chartConfigsProvider
     */
    public function testPreSetData(array $data, array $expected)
    {
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($expected);
        $event->expects($this->once())
            ->method('setData')
            ->with($data);

        $this->listener->preSetData($event);
    }

    public function chartConfigsProvider(): array
    {
        return [
            'name' => [
                'data'     => [
                    'name'        => 'chart',
                    'settings'    => [
                        'chart' => [
                            'option' => 'value'
                        ]
                    ],
                    'data_schema' => [
                        'chart' => [
                            'schema' => 'value'
                        ]
                    ],
                ],
                'expected' => [
                    'name'        => 'chart',
                    'settings'    => [
                        'option' => 'value'
                    ],
                    'data_schema' => [
                        'schema' => 'value'
                    ],
                ],
            ]
        ];
    }

    public function testEmptyData()
    {
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->exactly(2))
            ->method('getData')
            ->willReturn(['test']);
        $event->expects($this->atLeastOnce())
            ->method('setData')
            ->with([]);

        $this->listener->preSetData($event);
        $this->listener->onSubmit($event);
    }
}
