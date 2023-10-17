<?php

namespace Oro\Bundle\NotificationBundle\Event;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired after all recipients are calculated for a given notification rule and is aimed to allow process
 * recipients before notifications will be sent to them.
 */
class NotificationProcessRecipientsEvent extends Event
{
    public const NAME = 'oro.notification.event.notification_process_recipients';

    private object $entity;
    private array $recipients;

    public function __construct(object $entity, array $recipients)
    {
        $this->entity = $entity;
        $this->recipients = $recipients;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    /**
     * @return EmailHolderInterface[]
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    /**
     * @param EmailHolderInterface[] $recipients
     */
    public function setRecipients(array $recipients): void
    {
        $this->recipients = $recipients;
    }
}
