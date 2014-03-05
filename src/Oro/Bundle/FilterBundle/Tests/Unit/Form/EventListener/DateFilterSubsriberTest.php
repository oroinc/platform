<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\EventListener;

use Carbon\Carbon;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubscriber;

class DateFilterSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var DateFilterSubscriber */
    protected $subscriber;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $compiler;

    public function setUp()
    {
        $this->compiler   = $this->getMockBuilder('Oro\Bundle\FilterBundle\Expression\Date\Compiler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->subscriber = new DateFilterSubscriber($this->compiler);
    }

    public function testSubscribedEvents()
    {
        $events = DateFilterSubscriber::getSubscribedEvents();
        $this->assertCount(1, $events);

        $eventNames = array_keys($events);
        $this->assertEquals(FormEvents::PRE_SUBMIT, $eventNames[0]);
    }

    public function testProcessParams()
    {
        $data = [
            'start' => '{{4}}',
            'end'   => '{{6}}',
        ];

        $start = $end = Carbon::now();
        $start->firstOfYear();
        $end->firstOfMonth();

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        $this->compiler->expects($this->at(0))
            ->method('compile')
            ->with($data['start'])
            ->will($this->returnValue($start));

        $this->compiler->expects($this->at(1))
            ->method('compile')
            ->with($data['end'])
            ->will($this->returnValue($end));

        $this->subscriber->processParams($event);
    }
}
