<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model\Email;

use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Event\ReminderEvents;
use Oro\Bundle\ReminderBundle\Event\SendReminderEmailEvent;
use Oro\Bundle\ReminderBundle\Model\Email\EmailSendProcessor;
use Oro\Bundle\ReminderBundle\Model\Email\TemplateEmailNotification;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EmailSendProcessorTest extends \PHPUnit\Framework\TestCase
{
    const EXCEPTION_MESSAGE = 'message';

    /**
     * @var EmailSendProcessor
     */
    protected $processor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EmailNotificationManager
     */
    protected $emailNotificationManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TemplateEmailNotification
     */
    protected $emailNotification;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcher
     */
    protected $eventDispatcher;

    protected function setUp(): void
    {
        $this->emailNotificationManager = $this
            ->getMockBuilder(EmailNotificationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailNotification = $this->createMock(TemplateEmailNotification::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);

        $this->processor = new class(
            $this->emailNotificationManager,
            $this->emailNotification,
            $this->eventDispatcher
        ) extends EmailSendProcessor {
            public function xgetReminders(): array
            {
                return $this->reminders;
            }
        };
    }

    public function testPush()
    {
        $fooReminder = $this->createMock(Reminder::class);
        $barReminder = $this->createMock(Reminder::class);

        $this->processor->push($fooReminder);
        $this->processor->push($barReminder);

        static::assertEquals([$fooReminder, $barReminder], $this->processor->xgetReminders());
    }

    public function testProcess()
    {
        /** @var Reminder|MockObject $fooReminder */
        $fooReminder = $this->createMock(Reminder::class);
        /** @var Reminder|MockObject $barReminder */
        $barReminder = $this->createMock(Reminder::class);

        $this->emailNotification
            ->expects($this->exactly(2))
            ->method('setReminder')
            ->withConsecutive([$fooReminder], [$barReminder]);

        $this->emailNotificationManager
            ->expects($this->exactly(2))
            ->method('processSingle')
            ->with($this->emailNotification);

        $fooReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_SENT);

        $barReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_SENT);

        $barEvent = new SendReminderEmailEvent($barReminder);
        $fooEvent = new SendReminderEmailEvent($fooReminder);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$barEvent, ReminderEvents::BEFORE_REMINDER_EMAIL_NOTIFICATION_SEND],
                [$fooEvent, ReminderEvents::BEFORE_REMINDER_EMAIL_NOTIFICATION_SEND]
            );

        $this->processor->push($fooReminder);
        $this->processor->push($barReminder);

        $this->processor->process();
    }

    public function testProcessFailed()
    {
        /** @var Reminder|MockObject $fooReminder */
        $fooReminder = $this->createMock(Reminder::class);

        $this->emailNotification
            ->expects($this->once())
            ->method('setReminder')
            ->with($fooReminder);

        $exception = new \Exception();

        $this->emailNotificationManager
            ->expects($this->once())
            ->method('processSingle')
            ->with($this->emailNotification)
            ->will($this->throwException($exception));

        $fooReminder->expects($this->once())
            ->method('setState')
            ->with(Reminder::STATE_FAIL);

        $fooReminder->expects($this->once())
            ->method('setFailureException')
            ->with($exception);

        $this->processor->push($fooReminder);

        $this->processor->process();
    }

    public function testGetLabel()
    {
        $this->assertEquals(
            'oro.reminder.processor.email.label',
            $this->processor->getLabel()
        );
    }
}
