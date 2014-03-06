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
        $this->markTestSkipped('FIX IT');

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
}
