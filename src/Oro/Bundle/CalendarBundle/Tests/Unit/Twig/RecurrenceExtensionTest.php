<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Twig;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Twig\RecurrenceExtension;

class RecurrenceExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $delegateStrategy;

    /** @var RecurrenceExtension */
    protected $extension;

    protected function setUp()
    {
        $this->delegateStrategy = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Strategy\Recurrence\DelegateStrategy')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new RecurrenceExtension($this->delegateStrategy);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_recurrence', $this->extension->getName());
    }

    public function testGetRecurrencePattern()
    {
        $this->delegateStrategy->expects($this->once())
            ->method('getRecurrencePattern')
            ->willReturn('test_pattern');
        $this->assertEquals('test_pattern', $this->extension->getRecurrencePattern(new Recurrence()));
    }
}
