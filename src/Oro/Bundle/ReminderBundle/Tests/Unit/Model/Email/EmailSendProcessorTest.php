<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\Email;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\Email\EmailSendProcessor;

class EmailSendProcessorTest extends \PHPUnit_Framework_TestCase
{
    const EXCEPTION_MESSAGE = 'message';

    /**
     * @var EmailSendProcessor
     */
    protected $processor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailNotificationProcessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailNotification;

    protected function setUp()
    {
        $this->emailNotificationProcessor = $this
            ->getMockBuilder('Oro\\Bundle\\NotificationBundle\\Processor\\EmailNotificationProcessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailNotification = $this
            ->getMockBuilder('Oro\\Bundle\\ReminderBundle\\Model\\Email\\EmailNotification')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new EmailSendProcessor(
            $this->emailNotificationProcessor,
            $this->emailNotification
        );
    }

    public function testProcess()
    {
        $reminder = new Reminder();

        $this->emailNotification
            ->expects($this->once())
            ->method('setReminder');

        $this->emailNotificationProcessor
            ->expects($this->once())
            ->method('process');

        $this->processor->process($reminder);

        $this->assertEquals(Reminder::STATE_SENT, $reminder->getState());
    }

    public function testProcessFailed()
    {
        $reminder = new Reminder();

        $this->emailNotification
            ->expects($this->once())
            ->method('setReminder');

        $this->emailNotificationProcessor
            ->expects($this->once())
            ->method('process')
            ->will($this->throwException(new \Exception(self::EXCEPTION_MESSAGE)));

        $this->processor->process($reminder);

        $this->assertEquals(Reminder::STATE_FAIL, $reminder->getState());
        $this->assertEquals(self::EXCEPTION_MESSAGE, $reminder->getFailureException()['message']);
    }

    public function testGetName()
    {
        $this->assertEquals(
            EmailSendProcessor::NAME,
            $this->processor->getName()
        );
    }

    public function testGetLabel()
    {
        $this->assertEquals(
            'oro.reminder.processor.email.label',
            $this->processor->getLabel()
        );
    }
}
