<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model;

use Oro\Bundle\ReminderBundle\Model\ReminderSender;
use Oro\Bundle\ReminderBundle\Entity\Reminder;

class ReminderSenderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $processors;

    /**
     * @var ReminderSender
     */
    protected $sender;

    protected function setUp()
    {
        $this->processors = array(
            $this->getMock('Oro\\Bundle\\ReminderBundle\\Model\\SendProcessorInterface'),
            $this->getMock('Oro\\Bundle\\ReminderBundle\\Model\\SendProcessorInterface')
        );
        $this->sender = new ReminderSender($this->processors);
    }

    public function testSend()
    {
        $reminder = $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\Reminder');

        $this->processors[0]->expects($this->once())
            ->method('supports')
            ->with($reminder)
            ->will($this->returnValue(false));

        $this->processors[1]->expects($this->once())
            ->method('supports')
            ->with($reminder)
            ->will($this->returnValue(true));

        $this->processors[1]->expects($this->once())
            ->method('process')
            ->with($reminder)
            ->will($this->returnValue(true));

        $this->sender->send($reminder);
    }
}
