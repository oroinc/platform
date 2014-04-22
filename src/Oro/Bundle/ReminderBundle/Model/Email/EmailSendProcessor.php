<?php

namespace Oro\Bundle\ReminderBundle\Model\Email;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\SendProcessorInterface;

class EmailSendProcessor implements SendProcessorInterface
{
    const NAME = 'email';

    /**
     * @var EmailNotificationProcessor
     */
    protected $emailNotificationProcessor;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var EmailNotification
     */
    protected $emailNotification;

    /**
     * @var Reminder[]
     */
    protected $reminders = array();

    /**
     * @param EmailNotificationProcessor $emailNotificationProcessor
     * @param EmailNotification          $emailNotification
     */
    public function __construct(
        EmailNotificationProcessor $emailNotificationProcessor,
        EmailNotification $emailNotification
    ) {
        $this->emailNotificationProcessor = $emailNotificationProcessor;
        $this->emailNotification = $emailNotification;
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
     *
     * @param Reminder $reminder
     */
    public function sendReminderEmail(Reminder $reminder)
    {
        $this->emailNotification->setReminder($reminder);

        try {
            $this->emailNotificationProcessor->process(
                $this->emailNotification->getEntity(),
                [$this->emailNotification]
            );
            $reminder->setState(Reminder::STATE_SENT);
        } catch (\Exception $exception) {
            $reminder->setState(Reminder::STATE_FAIL);
            $reminder->setFailureException($exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.reminder.processor.email.label';
    }
}
