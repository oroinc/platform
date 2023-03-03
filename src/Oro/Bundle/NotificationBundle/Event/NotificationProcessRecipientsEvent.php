<?php

namespace Oro\Bundle\NotificationBundle\Event;

use Oro\Bundle\NotificationBundle\Helper\WebsiteAwareEntityHelper;
use Symfony\Contracts\EventDispatcher\Event;

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

    private WebsiteAwareEntityHelper $websiteAwareHelper;

    /**
     * @var array
     */
    private $recipients;

    /**
     * @param object $entity
     * @param array $recipients
     */
    public function __construct($entity, array $recipients, WebsiteAwareEntityHelper $websiteAwareHelper)
    {
        $this->entity = $entity;
        $this->recipients = $recipients;
        $this->websiteAwareHelper = $websiteAwareHelper;
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

    public function getWebsiteAwareEntityHelper(): WebsiteAwareEntityHelper
    {
        return $this->websiteAwareHelper;
    }
}
