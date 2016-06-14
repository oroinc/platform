<?php

namespace Oro\Bundle\NotificationBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Oro\Bundle\NotificationBundle\Entity\SpoolItem;

class NotificationSentEvent extends Event
{
    const NAME = 'oro.notification.event.notification_send_after';

    /**
     * @var SpoolItem
     */
    protected $spoolItem;

    /**
     * @var int
     */
    protected $sentCount;

    public function __construct($spoolItem, $sentCount)
    {
        $this->spoolItem = $spoolItem;
        $this->sentCount = $sentCount;
    }

    /**
     * Get spool item
     *
     * @return SpoolItem
     */
    public function getSpoolItem()
    {
        return $this->spoolItem;
    }

    /**
     * @return int
     */
    public function getSentCount()
    {
        return $this->sentCount;
    }
}
