<?php

namespace Oro\Bundle\ReminderBundle\Model\Email;

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
     * @param EmailNotificationProcessor $emailNotificationProcessor
     */
    public function __construct(EmailNotificationProcessor $emailNotificationProcessor)
    {
        $this->emailNotificationProcessor = $emailNotificationProcessor;
    }

    /**
     * Send reminder using email
     *
     * @param Reminder $reminder
     */
    public function process(Reminder $reminder)
    {
        // TODO: Implement process() method.
    }

    /**
     * If reminder email should be sent
     *
     * @param Reminder $reminder
     */
    public function supports(Reminder $reminder)
    {
        // TODO: Implement supports() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
