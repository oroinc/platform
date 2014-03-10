<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model;

use Oro\Bundle\ReminderBundle\Model\ReminderSender;
use Oro\Bundle\ReminderBundle\Entity\Reminder;

class ReminderSenderTest extends \PHPUnit_Framework_TestCase
{
    const FOO_METHOD = 'foo';
    const BAR_METHOD = 'bar';

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

        $this->processors[self::FOO_METHOD] = $this->getMockProcessor(self::FOO_METHOD);
        $this->processors[self::BAR_METHOD] = $this->getMockProcessor(self::BAR_METHOD);

        $this->sender = new ReminderSender($this->processors);
    }

    public function testSend()
    {
        $reminder = $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\Reminder');

        $reminder->expects($this->at(0))
            ->method('getMethod')
            ->will($this->returnValue(self::FOO_METHOD));

        $this->processors[self::FOO_METHOD]->expects($this->once())
            ->method('process')
            ->with($reminder);

        $reminder->expects($this->at(1))
            ->method('getState')
            ->will($this->returnValue(Reminder::STATE_SENT));

        $reminder->expects($this->at(2))
            ->method('setSentAt')
            ->with($this->isInstanceOf('DateTime'));

        $this->sender->send($reminder);
    }

    /**
     * @expectedException \Oro\Bundle\ReminderBundle\Exception\SendTypeNotSupportedException
     * @expectedExceptionMessage Reminder method "not_exists" is not supported.
     */
    public function testNonExistingProvider()
    {
        $reminder = $this->getMock('Oro\\Bundle\\ReminderBundle\\Entity\\Reminder');

        $reminder->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('not_exists'));

        $this->processors[self::FOO_METHOD]->expects($this->never())->method($this->anything());
        $this->processors[self::BAR_METHOD]->expects($this->never())->method($this->anything());

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
