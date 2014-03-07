<?php

namespace Oro\Bundle\ReminderBundle\Model\Email;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
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
        $entity = $this->em
            ->getRepository($reminder->getRelatedEntityClassName())
            ->find($reminder->getRelatedEntityId());

        $notification = new EmailNotification();

        $this->emailNotificationProcessor->process(
            $entity,
            [$notification]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
