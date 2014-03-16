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
     * Send reminder using email
     *
     * @param Reminder $reminder
     */
    public function process(Reminder $reminder)
    {
        $this->emailNotification->setReminder($reminder);

        $reminder->setState(Reminder::STATE_SENT);

        try {
            $this->emailNotificationProcessor->process(
                $this->emailNotification->getEntity(),
                [$this->emailNotification]
            );
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
