<?php

namespace Oro\Bundle\NotificationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * This event is fired after all recipients are calculated for a given notification rule and is aimed to allow process
 * recipients before notifications will be sent to them.
 */
class NotificationProcessRecipientsEvent extends Event
{
    public const NAME = 'oro.notification.event.notification_process_recipients';

    /**
     * @var object
     */
    private $entity;

    /**
     * @var array
     */
    private $recipients;

    /**
     * @param object $entity
     * @param array $recipients
     */
    public function __construct($entity, array $recipients)
    {
        $this->entity = $entity;
        $this->recipients = $recipients;
    }

    /**
     * @param array $recipients
     * @return NotificationProcessRecipientsEvent
     */
    public function setRecipients(array $recipients): self
    {
        $this->recipients = $recipients;

        return $this;
    }

    /**
     * @return array
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
