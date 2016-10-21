<?php

namespace Oro\Bundle\NotificationBundle\Model;

use Oro\Bundle\NotificationBundle\Model\EmailNotificationInterface;

interface SenderAwareEmailNotificationInterface extends EmailNotificationInterface
{
    /**
     * @return string|null
     */
    public function getSenderEmail();

    /**
     * @return string|null
     */
    public function getSenderName();
}
