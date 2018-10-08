<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ChartBundle\Form\EventListener\ChartTypeEventListener;
use Symfony\Component\Form\FormEvents;

class ChartTypeEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ChartTypeEventListener
     */
    protected $listener;

    protected function setUp()
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
     * @param array $data
     * @param array $expected
     *
     * @dataProvider chartConfigsProvider
     */

    public function testPostSubmit(array $data, array $expected)
    {
        $event = $this
            ->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event
            ->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        $event
            ->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($expected));

        $this->listener->onSubmit($event);
    }

    /**
     * @param array $data
     * @param array $expected
     *
     * @dataProvider chartConfigsProvider
     */

    public function testPreSetData(array $data, array $expected)
    {
        $event = $this
            ->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event
            ->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($expected));

        $event
            ->expects($this->once())
            ->method('setData')
            ->with($this->equalTo($data));

        $this->listener->preSetData($event);
    }

    /**
     * @return array
     */
    public function chartConfigsProvider()
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
        $event = $this
            ->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event
            ->expects($this->exactly(2))
            ->method('getData')
            ->will($this->returnValue(['test']));

        $event
            ->expects($this->atLeastOnce())
            ->method('setData')
            ->with($this->equalTo([]));

        $this->listener->preSetData($event);
        $this->listener->onSubmit($event);
    }
}
