<?php

namespace Oro\Bundle\NotificationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Represents an entity that may be notified by email messages
 */
interface NotificationEmailInterface
{
    /**
     * Gets an email addresses which can be used to send messages
     *
     * @return ArrayCollection
     */
    public function getNotificationEmails();
}
