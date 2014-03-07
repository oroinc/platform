<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model;

use Oro\Bundle\ReminderBundle\Model\ReminderSender;
use Oro\Bundle\ReminderBundle\Model\ReminderState;

class ReminderSenderTest extends \PHPUnit_Framework_TestCase
{
    const FOO_TYPE = 'foo';
    const BAR_TYPE = 'bar';

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
        $this->processors = array();

        $this->processors[self::FOO_TYPE] = $this->getMockProcessor(self::FOO_TYPE);
        $this->processors[self::BAR_TYPE] = $this->getMockProcessor(self::BAR_TYPE);

        $this->sender = new ReminderSender($this->processors);
    }

    public function testSend()
    {
        $reminder = $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\Reminder');
        $reminderState = $this->getMock('Oro\\Bundle\\ReminderBundle\\Model\\ReminderState');

        $reminder->expects($this->at(0))
            ->method('getState')
            ->will($this->returnValue($reminderState));

        $reminderState->expects($this->at(0))
            ->method('isAllSent')
            ->will($this->returnValue(false));

        $reminderState->expects($this->at(1))
            ->method('getSendTypeNames')
            ->will($this->returnValue(array(self::FOO_TYPE, self::BAR_TYPE)));

        $reminderState->expects($this->at(2))
            ->method('getSendTypeState')
            ->with(self::FOO_TYPE)
            ->will($this->returnValue(array(ReminderState::SEND_TYPE_NOT_SENT)));

        $this->processors[self::FOO_TYPE]->expects($this->once())
            ->method('process')
            ->with($reminder);

        $reminderState->expects($this->at(3))
            ->method('getSendTypeState')
            ->with(self::BAR_TYPE)
            ->will($this->returnValue(array(ReminderState::SEND_TYPE_SENT)));

        $this->processors[self::BAR_TYPE]->expects($this->once())
            ->method('process')
            ->with($reminder);

        $reminderState->expects($this->at(4))
            ->method('isAllSent')
            ->will($this->returnValue(true));

        $reminder->expects($this->at(1))
            ->method('setSent')
            ->with(true);

        $reminder->expects($this->at(2))
            ->method('setSentAt')
            ->with($this->isInstanceOf('DateTime'));

        $this->sender->send($reminder);
    }

    public function testSendWhenAllSent()
    {
        $reminder = $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\Reminder');
        $reminderState = $this->getMock('Oro\\Bundle\\ReminderBundle\\Model\\ReminderState');

        $reminder->expects($this->once())
            ->method('getState')
            ->will($this->returnValue($reminderState));

        $reminderState->expects($this->once())
            ->method('isAllSent')
            ->will($this->returnValue(true));

        $this->processors[self::FOO_TYPE]->expects($this->never())->method($this->anything());
        $this->processors[self::BAR_TYPE]->expects($this->never())->method($this->anything());

        $this->sender->send($reminder);
    }

    /**
     * @expectedException \Oro\Bundle\ReminderBundle\Exception\SendTypeNotSupportedException
     * @expectedExceptionMessage
     */
    public function testNonExistingProvider()
    {
        $reminder = $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\Reminder');
        $reminderState = $this->getMock('Oro\\Bundle\\ReminderBundle\\Model\\ReminderState');

        $reminderState->expects($this->once())
            ->method('isAllSent')
            ->will($this->returnValue(false));

        $reminderState->expects($this->once())
            ->method('getSendTypeNames')
            ->will($this->returnValue(array('not_exists')));

        $reminder->expects($this->once())
            ->method('getState')
            ->will($this->returnValue($reminderState));

        $this->processors[self::FOO_TYPE]->expects($this->never())->method($this->anything());
        $this->processors[self::BAR_TYPE]->expects($this->never())->method($this->anything());

        $this->sender->send($reminder);
    }

    protected function getMockProcessor($name)
    {
        $result = $this->getMock('Oro\\Bundle\\ReminderBundle\\Model\\SendProcessorInterface');
        $result->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));

        return $result;
    }
}
