<?php

namespace Oro\Bundle\ReminderBundle\Model\Email;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Event\ReminderEvents;
use Oro\Bundle\ReminderBundle\Event\SendReminderEmailEvent;
use Oro\Bundle\ReminderBundle\Model\SendProcessorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Sends reminder notification emails.
 */
class EmailSendProcessor implements SendProcessorInterface
{
    /**
     * @var EmailNotificationManager
     */
    protected $emailNotificationManager;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var TemplateEmailNotification
     */
    protected $emailNotification;

    /**
     * @var Reminder[]
     */
    protected $reminders = array();

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
        EmailNotificationManager $emailNotificationManager,
        TemplateEmailNotification $emailNotification,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->emailNotificationManager = $emailNotificationManager;
        $this->emailNotification = $emailNotification;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function push(Reminder $reminder)
    {
        $this->reminders[] = $reminder;
    }

    /**
     * Send all reminders using email
     */
    public function process()
    {
        foreach ($this->reminders as $reminder) {
            $this->sendReminderEmail($reminder);
        }
        $this->reminders = array();
    }

    /**
     * Send reminder using email
     */
    public function sendReminderEmail(Reminder $reminder)
    {
        $event = new SendReminderEmailEvent($reminder);
        $this->eventDispatcher->dispatch(
            $event,
            ReminderEvents::BEFORE_REMINDER_EMAIL_NOTIFICATION_SEND
        );
        $this->emailNotification->setReminder($reminder);

        try {
            $this->emailNotificationManager->processSingle($this->emailNotification);

            $reminder->setState(Reminder::STATE_SENT);
        } catch (\Exception $exception) {
            $reminder->setState(Reminder::STATE_FAIL);
            $reminder->setFailureException($exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.reminder.processor.email.label';
    }
}
