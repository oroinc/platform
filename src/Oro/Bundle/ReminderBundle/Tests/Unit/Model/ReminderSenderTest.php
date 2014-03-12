<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model;

use Oro\Bundle\ReminderBundle\Model\ReminderSender;
use Oro\Bundle\ReminderBundle\Entity\Reminder;

class ReminderSenderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ReminderSender
     */
    protected $sender;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Oro\\Bundle\\ReminderBundle\\Model\\SendProcessorRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sender = new ReminderSender($this->registry);
    }

    public function testSend()
    {
        $method = 'foo_method';

        $reminder = $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\Reminder');

        $reminder->expects($this->at(0))
            ->method('getMethod')
            ->will($this->returnValue($method));

        $processor = $this->getMock('Oro\\Bundle\\ReminderBundle\\Model\\SendProcessorInterface');

        $this->registry->expects($this->once())
            ->method('getProcessor')
            ->with($method)
            ->will($this->returnValue($processor));

        $processor->expects($this->once())
            ->method('process')
            ->with($reminder);

        $this->sender->send($reminder);
    }
}
